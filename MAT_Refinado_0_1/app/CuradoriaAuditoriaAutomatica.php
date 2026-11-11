<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRevisao.php';

/**
 * Auditoria estrutural de TODOS os dossies no MySQL, sem dependencias externas.
 * Nao faz verificacao historica online, nao cria notas, nao aprova nem homologa sozinha.
 * Classe deterministica e sem alteracao de esquema. Em lote somente triagem;
 * aprovar/rejeitar exige decisão documental individual na ficha do dossiê.
 */
final class CuradoriaAuditoriaAutomatica
{
    public const VERSAO = 'auditoria_prioridades_v2.1-modos';
    public const NOMES = [
        'apto' => 'Pronto para auditoria amostral',
        'fontes' => 'Conferir independência das fontes',
        'alerta' => 'Revisão histórica prioritária',
        'incompleto' => 'Dados ou notas pendentes',
        'finalizado' => 'Com decisão humana registrada',
        'contexto' => 'Sugestão: fora de escopo',
        'duplicata' => 'Sugestão: consolidar duplicata',
    ];

    /** Varredura completa em 3 consultas (evita 300 x 3 consultas individuais). */
    public static function carregar(PDO $pdo): array
    {
        $candidatos = $pdo->query('SELECT id_extracao,rodada,titulo_proposto,titulo_original,ano_original,ano_validado,analise_historica_json,periodo_historico,categoria,situacao_identificacao,status_avaliacao,estado_revisao_humana,homologado,pendencias_json,duplicatas_json FROM cur_candidatos ORDER BY rodada,id_extracao')->fetchAll(PDO::FETCH_ASSOC);
        $criterios = [];
        foreach ($pdo->query('SELECT id_extracao,codigo,nota,situacao,justificativa,fontes_ids_json FROM cur_criterios') as $r) {
            $criterios[(string)$r['id_extracao']][(string)$r['codigo']] = $r;
        }
        $fontes = [];
        foreach ($pdo->query('SELECT id_extracao,fonte_id,autor_instituicao,titulo,url FROM cur_fontes') as $r) {
            $fontes[(string)$r['id_extracao']][] = $r;
        }
        $itens = [];
        foreach ($candidatos as $r) {
            $id = (string)$r['id_extracao'];
            $r['criterios'] = $criterios[$id] ?? [];
            $r['fontes'] = $fontes[$id] ?? [];
            $r['pendencias'] = CuradoriaRodadas::decodeArray((string)($r['pendencias_json']??''));
            $r['duplicatas'] = CuradoriaRodadas::decodeArray((string)($r['duplicatas_json']??''));
            $itens[$id] = self::avaliar($r);
        }
        $amostra = self::amostragem(array_values($itens));
        foreach ($amostra as $id) $itens[$id]['amostra'] = true;
        $contagens = array_fill_keys(array_keys(self::NOMES),0);
        foreach ($itens as $item) $contagens[$item['categoria_auditoria']]++;
        return ['itens'=>$itens,'contagens'=>$contagens,'total'=>count($itens),'amostra'=>$amostra,'versao'=>self::VERSAO];
    }

    /** Testavel independentemente do MySQL. Tudo e sugestao operacional, nunca prova. */
    public static function avaliar(array $r): array
    {
        $impedimentos = CuradoriaRevisao::impedimentos($r);
        $estado = (string)($r['estado_revisao_humana']??'NAO_REVISADO');
        $r['impedimentos'] = $impedimentos;
        $r['amostra'] = false;
        $r['notas_validas'] = 0;
        foreach (CuradoriaRodadas::CRITERIOS as $codigo=>$rotulo) {
            $c = $r['criterios'][$codigo]??null;
            if($c && $c['nota']!==null && preg_match('/^[0-3]$/D',(string)$c['nota'])) $r['notas_validas']++;
        }
        $instituicoes = [];
        $semLink = 0;
        foreach ($r['fontes'] as $f) {
            $inst = trim(function_exists('mb_strtolower') ? mb_strtolower((string)($f['autor_instituicao']??''),'UTF-8') : strtolower((string)($f['autor_instituicao']??'')));
            if($inst!=='')$instituicoes[$inst]=true;
            $url = (string)($f['url']??'');
            if($url!=='' && CuradoriaRodadas::sourceUrl($url)===null)$semLink++;
        }
        $r['fontes_quantidade'] = count($r['fontes']);
        $r['instituicoes_quantidade'] = count($instituicoes);
        $r['avisos'] = [];
        $escopo = strtoupper((string)($r['situacao_identificacao']??''));
        $categoria = strtoupper((string)($r['categoria']??''));
        if($semLink>0)$r['avisos'][]='Há endereço de fonte com formato inválido.';
        $origem=$r['ano_original']??null;
        $validado=$r['ano_validado']??null;
        if ($validado!==null && $origem!==null && abs((int)$validado-(int)$origem)>=5) {
            $r['avisos'][]='Ano validado diverge do ano importado em cinco anos ou mais; confirmar justificativa histórica.';
        }
        if (($r['duplicatas']??[])!==[]) $r['avisos'][]='Possíveis duplicatas declaradas; não mesclar sem investigação.';
        if (($r['pendencias']??[])!==[]) $r['avisos'][]='Pendências históricas documentadas exigem revisão.';
        // Ordem metodologica: escopo e duplicatas PRIMEIRO, antes de cobrar 11 notas.
        // Isso evita que trechos contextuais sejam apresentados como erros de pontuacao.
        $eContexto = $escopo==='CONTEXTO_NAO_MARCO' || $categoria==='CONTEXTO_HISTORICO';
        $eDuplicata = $escopo==='POSSIVEL_DUPLICATA' || $categoria==='POSSIVEL_DUPLICATA' || ($r['duplicatas']??[])!==[];
        $r['orientacao'] = 'Conferir manualmente a fundamentação do dossiê.';
        if(in_array($estado,['APROVADO','REJEITADO','HOMOLOGADO'],true)) {
            $r['categoria_auditoria']='finalizado';
            $r['orientacao']='Decisão existente preservada; não modificar por uma nova varredura.';
        } elseif ($eContexto) {
            $r['categoria_auditoria']='contexto';
            $r['orientacao']='Não pontuar como inovação autônoma; conferir em lote se é contexto histórico ou se merece recorte mais específico.';
        } elseif ($eDuplicata) {
            $r['categoria_auditoria']='duplicata';
            $r['orientacao']='Conferir vínculo com o marco principal. Propor consolidação; não contar duas vezes no Têmporis.';
        } elseif ($r['notas_validas']<11 || (!CuradoriaDatacao::temData($r) && !(CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO && CuradoriaDatacao::anoValido($r['ano_original']??null))) || !$r['fontes']) {
            $r['categoria_auditoria']='incompleto';
            $r['orientacao']='Pesquisa complementar: completar evidências, corrigir data ou preencher critérios. Não é reprovação humana.';
        } elseif (($r['pendencias']??[])!==[] || $r['avisos']!==[]) {
            $r['categoria_auditoria']='alerta';
            $r['orientacao']='Há notas e data; auditar as ressalvas históricas. Este status não exige abrir cada ficha antes de gerar a amostra.';
        } elseif ($r['fontes_quantidade']<2 || $r['instituicoes_quantidade']<2) {
            $r['categoria_auditoria']='fontes';
            $r['orientacao']='Verificar independência documental. Duas fontes com nomes diferentes não provam independência histórica.';
        } elseif ($impedimentos!==[]) {
            $r['categoria_auditoria']='incompleto';
            $r['orientacao']='Corrigir vínculos ou justificativas na ficha; não é rejeição.';
        } else {
            $r['categoria_auditoria']='apto';
            $r['orientacao']='Sem impedimentos estruturais. Conferir evidências de uma amostra antes de aprovação humana.';
        }
        return $r;
    }

    /** Amostra reprodutivel dos dossies com 11 notas, inclusive com ressalvas; maximo 35, alterna rodadas. */
    public static function amostragem(array $items): array
    {
        $grupos=[];
        foreach($items as $r)if(in_array(($r['categoria_auditoria']??''),['apto','fontes','alerta'],true) && (int)($r['notas_validas']??0)===11){
            $grupos[(int)($r['rodada']??0)][] = (string)$r['id_extracao'];
        }
        if(!$grupos)return [];
        ksort($grupos,SORT_NUMERIC);
        $count = array_sum(array_map('count',$grupos));
        $meta = min(35,max(2,(int)ceil($count*.20)));
        $meta = min($meta,$count);
        foreach($grupos as &$ids)usort($ids,static fn($a,$b)=>strcmp(hash('sha256','TEMPORIS_AMOSTRA_2026_V1:'.$a),hash('sha256','TEMPORIS_AMOSTRA_2026_V1:'.$b)));
        unset($ids);
        $out=[];
        while(count($out)<$meta){
            $any=false;
            foreach($grupos as &$ids){
                if(!$ids)continue;
                $out[]=array_shift($ids);$any=true;
                if(count($out)>=$meta)break;
            }unset($ids);
            if(!$any)break;
        }
        return $out;
    }

    public static function filtro(array $snapshot, string $categoria, int $rodada, string $busca): array
    {
        $lower=static fn(string $v):string=>function_exists('mb_strtolower') ? mb_strtolower($v,'UTF-8') : strtolower($v);
        $busca=$lower(trim($busca));
        return array_values(array_filter($snapshot['itens'],static function(array $r) use($categoria,$rodada,$busca,$lower): bool {
            if($categoria==='amostra' && !$r['amostra'])return false;
            if($categoria!=='todos' && $categoria!=='amostra' && $r['categoria_auditoria']!==$categoria)return false;
            if($rodada>0 && (int)$r['rodada']!==$rodada)return false;
            if($busca!=='' && strpos($lower((string)$r['id_extracao'].' '.(string)$r['titulo_proposto']),$busca)===false)return false;
            return true;
        }));
    }

    /** Uma acao POST pode alterar no maximo 50 dossies; tudo ou nada em uma transacao. */
    public static function registrar(PDO $pdo, array $dados, array $usuario): string
    {
        $acao = (string)($dados['operacao']??'');
        if(!in_array($acao,['registrar_analise','encaminhar_pendencias','marcar_revisao'],true))
            throw new InvalidArgumentException('Aprovação/rejeição em lote desativada: decida cada dossiê após conferir suas fontes.');
        $motivo=trim((string)($dados['motivo']??''));
        $snapshot=self::carregar($pdo);
        if($acao==='registrar_analise'){
            $resumo=['versao'=>self::VERSAO,'tipo'=>'AUDITORIA_ESTRUTURAL_NAO_CIENTIFICA','usuario_id'=>$usuario['id']??null,'usuario'=>$usuario['nome']??'Pesquisador','total'=>$snapshot['total'],'contagens'=>$snapshot['contagens'],'amostra'=>$snapshot['amostra']];
            $pdo->prepare('INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (NULL,?,?)')->execute(['VARREDURA_AUTOMATICA',json_encode($resumo,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            return 'Análise automática registrada. Nenhum candidato foi aprovado ou alterado.';
        }
        if($acao==='encaminhar_pendencias'){
            // COMPATIBILIDADE: antigamente marcava TODO o banco como EM_REVISAO.
            // Agora registra a fila calculada, sem transformar o estado humano.
            $resumo=['versao'=>self::VERSAO,'tipo'=>'PLANO_DE_PRIORIDADES_SEM_MUDANCA_ESTADO',
                'usuario_id'=>$usuario['id']??null,'contagens'=>$snapshot['contagens'],'amostra'=>$snapshot['amostra']];
            $pdo->prepare('INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (NULL,?,?)')
                ->execute(['FILA_PRIORIDADES_GERADA',json_encode($resumo,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            return 'Fila de prioridades organizada automaticamente. Os estados humanos dos dossiês permaneceram inalterados.';
        }
        if(strlen($motivo)<15 || strlen($motivo)>2500)throw new InvalidArgumentException('Descreva a decisão em 15 a 2.500 caracteres.');
        $ids=$dados['ids']??[];
        if(!is_array($ids))throw new InvalidArgumentException('Seleção inválida.');
        $ids=array_values(array_unique(array_map('strval',$ids)));
        if(!$ids || count($ids)>50)throw new InvalidArgumentException('Selecione de 1 a 50 dossiês por operação.');
        foreach($ids as $id)if(!CuradoriaRodadas::validId($id)||!isset($snapshot['itens'][$id]))throw new InvalidArgumentException('Há um ID inválido na seleção.');
        $pdo->beginTransaction();
        try {
            // Lock antes de reavaliar; evita homologação/edição concorrente entre varredura e gravação.
            $lock=$pdo->prepare('SELECT id_extracao FROM cur_candidatos WHERE id_extracao=? FOR UPDATE');
            $update=$pdo->prepare("UPDATE cur_candidatos SET estado_revisao_humana=?,homologado=0 WHERE id_extracao=? AND homologado=0");
            $log=$pdo->prepare('INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (?,?,?)');
            foreach($ids as $id){
                $lock->execute([$id]);
                if(!$lock->fetchColumn())throw new RuntimeException('Dossiê não encontrado: '.$id);
                $r=CuradoriaRodadas::record($pdo,$id);
                if(!$r)throw new RuntimeException('Dossiê indisponível: '.$id);
                $avaliado=self::avaliar($r);
                $anterior=(string)($r['estado_revisao_humana']??'NAO_REVISADO');
                if(in_array($anterior,['APROVADO','REJEITADO','HOMOLOGADO'],true) || (int)$r['homologado']!==0)throw new InvalidArgumentException($id.' já possui decisão. Reabra individualmente se necessário.');
                $destino='EM_REVISAO';
                $update->execute([$destino,$id]);
                $auditoria=['versao'=>self::VERSAO,'autor_id'=>$usuario['id']??null,'revisor'=>$usuario['nome']??'Pesquisador','motivo'=>$motivo,'antes'=>$anterior,'depois'=>$destino,'categoria_auditoria'=>$avaliado['categoria_auditoria'],'amostra_sugerida'=>in_array($id,$snapshot['amostra'],true),'confirmacao_humana'=>false,'observacao'=>'Somente encaminhamento em lote; decisões finais são individuais.'];
                $log->execute([$id,'ENCAMINHADO_PARA_REVISAO',json_encode($auditoria,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
            }
            $pdo->commit();
            return count($ids).' dossiê(s) atualizado(s) com histórico. Homologação permanece individual.';
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
