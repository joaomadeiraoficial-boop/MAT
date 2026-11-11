<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';
require_once __DIR__.'/CuradoriaDatacao.php';
require_once __DIR__.'/CuradoriaPolitica.php';

/** Fluxo humano de revisao, sem apagamentos e sem alterar formulas matematicas. */
final class CuradoriaRevisao {
    public const ESTADOS = [
        'NAO_REVISADO'=>'Aguardando auditoria',
        'EM_REVISAO'=>'Em revisão',
        'APROVADO'=>'Aprovado pelo pesquisador',
        'REJEITADO'=>'Rejeitado (preservado)',
        'HOMOLOGADO'=>'Homologado para a série',
    ];
    /** O simplificado avalia a aptidão PRELIMINAR; o rigoroso continua obrigatório para homologar. */
    public static function impedimentos(array $r, ?string $modo=null): array {
        $modo=$modo ?? CuradoriaPolitica::modo();
        if($modo===CuradoriaPolitica::SIMPLIFICADO) return self::impedimentosPreliminares($r);
        return self::impedimentosRigorosos($r);
    }

    private static function impedimentosPreliminares(array $r): array {
        $erros=[];
        $titulo=trim((string)($r['titulo_proposto']??''));
        if($titulo==='')$erros[]='Identificar o acontecimento histórico.';
        $situacao=strtoupper((string)($r['situacao_identificacao']??''));
        $categoria=strtoupper((string)($r['categoria']??''));
        if($situacao==='CONTEXTO_NAO_MARCO' || $categoria==='CONTEXTO_HISTORICO')
            $erros[]='Registro classificado como contexto, não como acontecimento tecnológico individualizado.';
        if(!CuradoriaDatacao::temData($r) && !CuradoriaDatacao::anoValido($r['ano_original']??null))
            $erros[]='Informar um ano de referência ou intervalo histórico válido.';
        $fontes=[];
        foreach(($r['fontes']??[]) as $fonte) if(trim((string)($fonte['fonte_id']??''))!=='')$fontes[]=$fonte;
        if(!$fontes)$erros[]='Vincular pelo menos uma fonte documental.';
        foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$nome){
            $c=$r['criterios'][$codigo]??null;
            if(!$c || !in_array((string)($c['nota']??''),['1','2','3'],true))
                $erros[]='Atribuir nota de 1 a 3 (pode ser 1 provisória): '.$nome.'.';
        }
        return array_values(array_unique($erros));
    }

    private static function impedimentosRigorosos(array $r): array {
        $erros=[];
        if(!CuradoriaDatacao::temData($r))
            $erros[]='Conferir o ano histórico ou registrar intervalo fundamentado.';
        if(!CuradoriaDatacao::conferirFonte($r))$erros[]='A fonte da datação intervalar não está cadastrada no dossiê.';
        if(!empty($r['pendencias'])) $erros[]='Resolver e documentar as pendências históricas.';
        if(!empty($r['duplicatas'])) $erros[]='Conferir possíveis duplicatas e registrar a decisão.';
        $fontes=[];
        foreach(($r['fontes']??[]) as $f){
            if(!empty($f['fonte_id'])) $fontes[(string)$f['fonte_id']]=true;
        }
        if(!$fontes) $erros[]='Vincular pelo menos uma fonte documental.';
        foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$nome) {
            $c=$r['criterios'][$codigo]??null;
            if(!$c || $c['nota']===null || !preg_match('/^[0-3]$/D',(string)$c['nota'])) {
                $erros[]='Pontuar com evidência: '.$nome.'.'; continue;
            }
            if(($c['situacao']??'')===CuradoriaPolitica::SITUACAO_MINIMA) $erros[]='Substituir nota mínima provisória por evidência: '.$nome.'.';
            if(trim((string)($c['justificativa']??''))==='') $erros[]='Justificar a nota: '.$nome.'.';
            $ligadas=CuradoriaRodadas::decodeArray((string)($c['fontes_ids_json']??''));
            if(!$ligadas) $erros[]='Informar a fonte vinculada ao critério: '.$nome.'.';
            foreach($ligadas as $fid) if(!isset($fontes[(string)$fid])){
                $erros[]='Fonte '.$fid.' não cadastrada para '.$nome.'.'; break;
            }
        }
        return array_values(array_unique($erros));
    }

    /** Detecta edição concorrente de pendências entre abertura e envio da ficha. */
    public static function hashItens(array $r): string {
        return hash('sha256',json_encode([
            'pendencias'=>$r['pendencias']??[],
            'duplicatas'=>$r['duplicatas']??[],
            'ano'=>$r['ano_validado']??null,
            'datacao_intervalar'=>CuradoriaDatacao::intervalo($r),
        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    /** Índices explícitos: só itens individualmente conferidos podem ser retirados de uma lista. */
    public static function filtrarItensRevisados(array $itens, mixed $selecionados): array {
        if(!is_array($selecionados)) throw new InvalidArgumentException('Seleção de itens inválida.');
        $indices=[];
        foreach($selecionados as $valor){
            $s=(string)$valor;
            if(!preg_match('/^(0|[1-9][0-9]*)$/D',$s) || (int)$s>=count($itens))
                throw new InvalidArgumentException('Índice de pendência inválido. Atualize a página antes de salvar.');
            $indices[(int)$s]=true;
        }
        return [array_values(array_filter($itens,static fn($v,$i)=>!isset($indices[$i]),ARRAY_FILTER_USE_BOTH)),
            array_values(array_filter($itens,static fn($v,$i)=>isset($indices[$i]),ARRAY_FILTER_USE_BOTH))];
    }

    /** Uma justificativa própria por item selecionado, não uma caixa para encerrar tudo. */
    public static function pareceresIndividuais(array $originais, mixed $selecionados, mixed $pareceres,
        array $fontes, mixed $referencias): array {
        if(!is_array($selecionados) || !is_array($pareceres) || !is_array($referencias))
            throw new InvalidArgumentException('Os pareceres e fontes individuais são obrigatórios para resolver itens.');
        $fontesExistentes=[];
        foreach($fontes as $f) {
            $id=trim((string)($f['fonte_id']??''));
            if($id!=='')$fontesExistentes[$id]=true;
        }
        $out=[];
        foreach($selecionados as $valor){
            $idx=(string)$valor;
            if(!preg_match('/^(0|[1-9][0-9]*)$/D',$idx) || !array_key_exists((int)$idx,$originais))
                throw new InvalidArgumentException('Índice documental inválido. Atualize a página.');
            $texto=trim((string)($pareceres[(int)$idx]??''));
            if(strlen($texto)<40 || strlen($texto)>2000)
                throw new InvalidArgumentException('Cada item marcado precisa de justificativa própria de 40 a 2.000 caracteres, citando a evidência.');
            $fonte=trim((string)($referencias[(int)$idx]??''));
            if($fonte==='' || !isset($fontesExistentes[$fonte]))
                throw new InvalidArgumentException('Cada pendência marcada requer uma fonte existente no próprio dossiê.');
            $out[$idx]=['item'=>$originais[(int)$idx],'parecer'=>$texto,
                'indice'=>(int)$idx,'fonte_id'=>$fonte];
        }
        return $out;
    }

    /** Permite completar, em uma unica transacao, rubricas que ainda estao sem nota.
     * Nunca reescreve notas preexistentes nem pontua sem fonte/justificativa. */
    public static function validarRubricasComplementares(mixed $entradas, array $criterios, array $fontes): array {
        if(!is_array($entradas))throw new InvalidArgumentException('As rubricas complementares devem ser enviadas como campos validos.');
        $permitidas=[];
        foreach($fontes as $f){$fid=(string)($f['fonte_id']??'');if($fid!=='')$permitidas[$fid]=true;}
        $out=[];
        foreach($entradas as $codigo=>$campos){
            if(!is_string($codigo) || !isset(CuradoriaRodadas::CRITERIOS[$codigo]) || !is_array($campos))
                throw new InvalidArgumentException('Foi recebida uma rubrica desconhecida. Recarregue a ficha.');
            $nota=trim((string)($campos['nota']??''));
            $just=trim((string)($campos['justificativa']??''));
            $fonte=trim((string)($campos['fonte_id']??''));
            if($nota==='' && $just==='' && $fonte==='')continue; // Deixar em branco nao inventa pontuacao.
            if(($criterios[$codigo]['nota']??null)!==null)
                throw new InvalidArgumentException('Esta rubrica ja foi pontuada em outra edicao. Recarregue a ficha antes de salvar.');
            if(!in_array($nota,['1','2','3'],true))
                throw new InvalidArgumentException('Escolha uma nota de 1 a 3 para '.CuradoriaRodadas::CRITERIOS[$codigo].'.');
            if(strlen($just)<25 || strlen($just)>1500)
                throw new InvalidArgumentException('Escreva ao menos 25 caracteres de evidencia para '.CuradoriaRodadas::CRITERIOS[$codigo].'.');
            if($fonte==='' || !isset($permitidas[$fonte]))
                throw new InvalidArgumentException('Selecione uma fonte cadastrada para '.CuradoriaRodadas::CRITERIOS[$codigo].'.');
            $out[$codigo]=['nota'=>(int)$nota,'justificativa'=>$just,'fonte_id'=>$fonte];
        }
        return $out;
    }

    /** Modificações exigem sessao admin, CSRF e motivo humano em actions.php. */
    public static function executar(PDO $pdo, string $id, string $acao, array $dados, array $user): string {
        if(!CuradoriaRodadas::validId($id)) throw new InvalidArgumentException('Identificador inválido.');
        if(!in_array($acao,['em_revisao','rejeitar','aprovar','homologar','reabrir','metadados','criterio','fonte'],true))
            throw new InvalidArgumentException('Ação não permitida.');
        $motivo=trim((string)($dados['motivo']??''));
        if(strlen($motivo)<15 || strlen($motivo)>2500)
            throw new InvalidArgumentException('Registre uma justificativa de 15 a 2.500 caracteres para a auditoria.');
        $pdo->beginTransaction();
        try {
            $st=$pdo->prepare('SELECT id_extracao FROM cur_candidatos WHERE id_extracao=? FOR UPDATE');
            $st->execute([$id]);
            if(!$st->fetchColumn()) throw new RuntimeException('Dossiê não encontrado.');
            $r=CuradoriaRodadas::record($pdo,$id);
            if(!$r) throw new RuntimeException('Dossiê indisponível.');
            $antes=['estado'=>$r['estado_revisao_humana'],'homologado'=>(int)$r['homologado'], 'ano_validado'=>$r['ano_validado'],'datacao_intervalar'=>CuradoriaDatacao::intervalo($r)];
            $prev=(string)$r['estado_revisao_humana'];
            if($acao==='reabrir'){
                if($prev!=='HOMOLOGADO') throw new InvalidArgumentException('Somente dossiê homologado pode ser reaberto.');
                $pdo->prepare("UPDATE cur_candidatos SET estado_revisao_humana='EM_REVISAO',homologado=0 WHERE id_extracao=?")->execute([$id]);
            } elseif($acao==='metadados') {
                if($prev==='HOMOLOGADO') throw new InvalidArgumentException('Reabra a homologação antes de corrigir os dados.');
                $modo=trim((string)($dados['modo_datacao']??(CuradoriaDatacao::intervalo($r)!==null?'intervalo':'pontual')));
                if(!in_array($modo,['pontual','intervalo'],true)) throw new InvalidArgumentException('Tipo de datação inválido.');
                $ano=trim((string)($dados['ano_validado']??''));
                if($modo==='pontual' && $ano!=='' && !CuradoriaDatacao::anoValido($ano))
                    throw new InvalidArgumentException('Ano inválido; utilize um ano inteiro (negativo para a.C.).');
                $analise=CuradoriaDatacao::analise($r);
                if($modo==='intervalo'){
                    $analise[CuradoriaDatacao::CHAVE]=CuradoriaDatacao::validarEntrada($dados,$r['fontes']);
                    $ano=''; // Nunca falsificar data exata quando o intervalo é documentado.
                } else {
                    unset($analise[CuradoriaDatacao::CHAVE]);
                }
                $pendAtual=$r['pendencias'];$dupsAtual=$r['duplicatas'];
                $hashEnviado=(string)($dados['hash_itens']??'');
                if($hashEnviado==='' || !hash_equals(self::hashItens($r),$hashEnviado))
                    throw new InvalidArgumentException('Este dossiê foi alterado desde a abertura. Recarregue a página para não resolver a pendência errada.');
                $selecionaPend=$dados['pendencias_resolvidas']??[];
                $selecionaDupl=$dados['duplicatas_resolvidas']??[];
                [$pendRestantes,$pendRemovidas]=self::filtrarItensRevisados($pendAtual,$selecionaPend);
                [$dupsRestantes,$dupsRemovidas]=self::filtrarItensRevisados($dupsAtual,$selecionaDupl);
                if(!empty($dados['resolver_pendencias']) || !empty($dados['resolver_duplicatas']))
                    throw new InvalidArgumentException('Resolução global desativada. Selecione os itens e justifique cada um individualmente.');
                $resolucaoNova=(count($pendRemovidas)+count($dupsRemovidas))>0;
                if($resolucaoNova && $hashEnviado==='')
                    throw new InvalidArgumentException('Reabra a ficha: hash obrigatório para resolver pendências.');
                if($resolucaoNova){
                    if(empty($dados['confirmacao_documental'])) throw new InvalidArgumentException('Confirme que verificou individualmente os itens marcados.');
                    if(strlen($motivo)<60) throw new InvalidArgumentException('A resolução exige parecer geral de pelo menos 60 caracteres.');
                }
                $pareceresPend=self::pareceresIndividuais($pendAtual,$selecionaPend,$dados['parecer_pendencias']??[],$r['fontes'],$dados['fonte_pendencias']??[]);
                $pareceresDup=self::pareceresIndividuais($dupsAtual,$selecionaDupl,$dados['parecer_duplicatas']??[],$r['fontes'],$dados['fonte_duplicatas']??[]);
                // Campos opcionais para finalizar varias rubricas faltantes no mesmo salvamento.
                $rubricasComplementares=self::validarRubricasComplementares($dados['criterios_complementares']??[], $r['criterios'], $r['fontes']);
                $novoAno=$ano!==''?(int)$ano:null;
                $dataMudou=($novoAno===null ? $r['ano_validado']!==null : (string)($r['ano_validado']??'')!==(string)$novoAno);
                $analiseMudou=json_encode($analise,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)!==json_encode(CuradoriaDatacao::analise($r),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                if(!$resolucaoNova && !$dataMudou && !$analiseMudou && !$rubricasComplementares)
                    throw new InvalidArgumentException('Nada foi alterado. Para resolver uma pendencia marque sua caixa, escolha uma fonte e escreva o parecer; para concluir as notas, preencha as rubricas abaixo.');
                $antes['pareceres_individuais']=['pendencias'=>$pareceresPend,'duplicatas'=>$pareceresDup];
                $antes['pendencias_removidas_nesta_acao']=$pendRemovidas;
                $antes['duplicatas_removidas_nesta_acao']=$dupsRemovidas;
                $pendencias=json_encode($pendRestantes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                $duplicatas=json_encode($dupsRestantes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                $st=$pdo->prepare("UPDATE cur_candidatos SET ano_validado=?,analise_historica_json=?,pendencias_json=?,duplicatas_json=?,estado_revisao_humana='EM_REVISAO',homologado=0 WHERE id_extracao=?");
                $st->execute([$novoAno,json_encode($analise,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$pendencias,$duplicatas,$id]);
                if($rubricasComplementares){
                    $q=$pdo->prepare('INSERT INTO cur_criterios (id_extracao,codigo,nota,situacao,justificativa,evidencias_json,fontes_ids_json,o_que_falta) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nota=VALUES(nota),situacao=VALUES(situacao),justificativa=VALUES(justificativa),fontes_ids_json=VALUES(fontes_ids_json),o_que_falta=VALUES(o_que_falta)');
                    foreach($rubricasComplementares as $codigo=>$complemento){
                        $q->execute([$id,$codigo,$complemento['nota'],'PROPOSTA_REVISADA',$complemento['justificativa'],null,json_encode([$complemento['fonte_id']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),null]);
                    }
                }

            } elseif($acao==='fonte') {
                if($prev==='HOMOLOGADO') throw new InvalidArgumentException('Reabra a homologação antes de adicionar uma fonte.');
                $fid=trim((string)($dados['fonte_id']??''));
                if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{1,47}$/D',$fid)) throw new InvalidArgumentException('ID de fonte inválido. Use de 2 a 48 letras, números, pontos, hifens ou sublinhados.');
                $autor=trim((string)($dados['autor_instituicao']??''));
                $titulo=trim((string)($dados['titulo_fonte']??''));
                $descricao=trim((string)($dados['descricao_evidencia']??''));
                $url=trim((string)($dados['url']??''));
                $anoFonte=trim((string)($dados['ano_fonte']??''));
                if(strlen($autor)<3 || strlen($autor)>180 || strlen($titulo)<5 || strlen($titulo)>255)
                    throw new InvalidArgumentException('Informe autor/instituição (3–180) e título da fonte (5–255).');
                if(strlen($descricao)<25 || strlen($descricao)>4000)throw new InvalidArgumentException('Descreva a evidência específica (25–4.000 caracteres).');
                if($url!=='' && (strlen($url)>500 || CuradoriaRodadas::sourceUrl($url)===null))throw new InvalidArgumentException('Endereço da fonte inválido (apenas HTTP ou HTTPS).');
                if($anoFonte!=='' && !CuradoriaDatacao::anoValido($anoFonte))
                    throw new InvalidArgumentException('Ano da fonte inválido.');
                $st=$pdo->prepare('SELECT COUNT(*) FROM cur_fontes WHERE id_extracao=? AND fonte_id=?');
                $st->execute([$id,$fid]);
                if((int)$st->fetchColumn()>0)throw new InvalidArgumentException('Este ID de fonte já está cadastrado neste dossiê.');
                $pdo->prepare('INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$id,$fid,$autor,$titulo,$anoFonte===''?null:(int)$anoFonte,$descricao,$url===''?null:$url,'FONTE_EXTERNA_INSTITUCIONAL']);
                $pdo->prepare("UPDATE cur_candidatos SET estado_revisao_humana='EM_REVISAO',homologado=0 WHERE id_extracao=?")->execute([$id]);
                $depoisFonte=['fonte_id'=>$fid,'autor_instituicao'=>$autor,'titulo'=>$titulo,'ano'=>$anoFonte,'descricao_evidencia'=>$descricao,'url'=>$url];
            } elseif($acao==='criterio') {
                if($prev==='HOMOLOGADO') throw new InvalidArgumentException('Reabra a homologação antes de alterar notas.');
                $codigo=(string)($dados['codigo']??'');
                if(!isset(CuradoriaRodadas::CRITERIOS[$codigo])) throw new InvalidArgumentException('Critério desconhecido.');
                $nota=trim((string)($dados['nota']??''));
                if(!in_array($nota,['SEM_EVIDENCIA','0','1','2','3'],true)) throw new InvalidArgumentException('Nota inválida.');
                $just=trim((string)($dados['justificativa']??''));
                $ids=array_values(array_unique(array_filter(array_map('trim',explode(',',(string)($dados['fontes_ids']??''))))));
                if($nota!=='SEM_EVIDENCIA'){
                    if(strlen($just)<25) throw new InvalidArgumentException('Descreva a evidência com pelo menos 25 caracteres.');
                    if(!$ids) throw new InvalidArgumentException('Identifique pelo menos uma fonte cadastrada.');
                    $permitidas=array_column($r['fontes'],'fonte_id');
                    foreach($ids as $fid) if(!in_array($fid,$permitidas,true)) throw new InvalidArgumentException('Fonte não cadastrada: '.$fid);
                }
                $antes['criterio']=$r['criterios'][$codigo]??null;
                $st=$pdo->prepare('INSERT INTO cur_criterios (id_extracao,codigo,nota,situacao,justificativa,evidencias_json,fontes_ids_json,o_que_falta) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nota=VALUES(nota),situacao=VALUES(situacao),justificativa=VALUES(justificativa),fontes_ids_json=VALUES(fontes_ids_json),o_que_falta=VALUES(o_que_falta)');
                $st->execute([$id,$codigo,$nota==='SEM_EVIDENCIA'?null:(int)$nota,$nota==='SEM_EVIDENCIA'?'SEM_EVIDENCIA':'PROPOSTA_REVISADA',$just,null,json_encode($ids,JSON_UNESCAPED_UNICODE),$nota==='SEM_EVIDENCIA'?'Aguardar pesquisa adicional.':null]);
                $pdo->prepare("UPDATE cur_candidatos SET estado_revisao_humana='EM_REVISAO',homologado=0 WHERE id_extracao=?")->execute([$id]);
            } else {
                if(in_array($acao,['aprovar','homologar'],true)){
                    if(empty($dados['confirmacao_humana']))throw new InvalidArgumentException('Assinale a conferência humana das fontes e justificativas.');
                    $bloqueios=self::impedimentos($r,$acao==='homologar'?CuradoriaPolitica::RIGOROSO:CuradoriaPolitica::modo());
                    if($bloqueios)throw new InvalidArgumentException('Aprovação bloqueada: '.implode(' | ',array_slice($bloqueios,0,4)));
                }
                if($acao==='homologar' && $prev!=='APROVADO') throw new InvalidArgumentException('Primeiro aprove o dossiê; a homologação é uma segunda decisão.');
                if($prev==='HOMOLOGADO') throw new InvalidArgumentException('Dossiê já homologado. Utilize Reabrir para nova auditoria.');
                $destino=match($acao){'em_revisao'=>'EM_REVISAO','rejeitar'=>'REJEITADO','aprovar'=>'APROVADO','homologar'=>'HOMOLOGADO'};
                $st=$pdo->prepare('UPDATE cur_candidatos SET estado_revisao_humana=?,homologado=? WHERE id_extracao=?');
                $st->execute([$destino,$acao==='homologar'?1:0,$id]);
            }
            // A operacao so pode anunciar sucesso quando os dados persistidos forem relidos.
            $novo=CuradoriaRodadas::record($pdo,$id);
            if(!$novo)throw new RuntimeException('Não foi possível reler o dossiê após a gravação.');
            if($acao==='metadados'){
                if($novo['pendencias']!==$pendRestantes || $novo['duplicatas']!==$dupsRestantes ||
                    ($novoAno===null ? $novo['ano_validado']!==null : (string)($novo['ano_validado']??'')!==(string)$novoAno))
                    throw new RuntimeException('A verificacao das pendencias gravadas falhou: operacao desfeita para proteger o trabalho.');
                foreach($rubricasComplementares as $codigo=>$complemento){
                    $gravado=$novo['criterios'][$codigo]??null;
                    $ligadas=$gravado ? CuradoriaRodadas::decodeArray((string)($gravado['fontes_ids_json']??'')) : [];
                    if(!$gravado || (int)($gravado['nota']??-1)!==$complemento['nota'] ||
                        (string)($gravado['justificativa']??'')!==$complemento['justificativa'] ||
                        !in_array($complemento['fonte_id'],$ligadas,true))
                        throw new RuntimeException('Uma rubrica não foi confirmada no banco: operação desfeita para evitar perda de dados.');
                }
            }
            if($acao==='criterio'){
                $gravado=$novo['criterios'][(string)$dados['codigo']]??null;
                $esperado=$nota==='SEM_EVIDENCIA'?null:(int)$nota;
                $recebido=$gravado['nota']??null;
                $recebido=$recebido===null?null:(int)$recebido;
                if(!$gravado || $recebido!==$esperado || (string)($gravado['justificativa']??'')!==$just)
                    throw new RuntimeException('A verificacao da nota gravada falhou: operacao desfeita.');
                $idsPersistidos=CuradoriaRodadas::decodeArray((string)($gravado['fontes_ids_json']??''));
                if($idsPersistidos!==$ids)throw new RuntimeException('A verificacao das fontes da nota gravada falhou: operacao desfeita.');
            }
            $depois=['estado'=>$novo['estado_revisao_humana'],'homologado'=>(int)$novo['homologado'],'ano_validado'=>$novo['ano_validado'],'datacao_intervalar'=>CuradoriaDatacao::intervalo($novo)];
            if($acao==='criterio') $depois['criterio']=$novo['criterios'][(string)$dados['codigo']]??null;
            if($acao==='fonte') $depois['fonte']=$depoisFonte??null;
            if($acao==='metadados'){
                $antes['pendencias']=$r['pendencias'];$antes['duplicatas']=$r['duplicatas'];
                $depois['pendencias']=$novo['pendencias'];$depois['duplicatas']=$novo['duplicatas'];
                $depois['rubricas_complementares']=$rubricasComplementares;
            }
            $auditoria=['usuario_id'=>(string)($user['id']??''),'revisor'=>(string)($user['nome']??'Pesquisador'),
                'acao'=>$acao,'motivo'=>$motivo,'politica_curadoria'=>($acao==='homologar'?CuradoriaPolitica::RIGOROSO:CuradoriaPolitica::modo()),'antes'=>$antes,'depois'=>$depois];
            $log=$pdo->prepare('INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (?,?,?)');
            $log->execute([$id,'REVISAO_HUMANA_'.strtoupper($acao),json_encode($auditoria,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
            if($acao==='metadados'){
                foreach(['PENDENCIA_RESOLVIDA'=>$pareceresPend??[],'DUPLICATA_ANALISADA'=>$pareceresDup??[]] as $tipo=>$itens){
                    foreach($itens as $item){
                        $log->execute([$id,$tipo,json_encode([
                            'usuario_id'=>(string)($user['id']??''),'revisor'=>(string)($user['nome']??''),
                            'item'=>$item['item'],'indice'=>$item['indice'],'parecer'=>$item['parecer'],'fonte_id'=>$item['fonte_id'],
                            'hash_anterior'=>self::hashItens($r)
                        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
                    }
                }
            }
            $restantes=self::impedimentos($novo);
            $pdo->commit();
            if($acao==='metadados'){
                $feitas=count($rubricasComplementares);
                $resolvidas=count($pendRemovidas)+count($dupsRemovidas);
                return 'SALVAMENTO CONFIRMADO NO BANCO: '.$feitas.' rubrica(s) concluida(s), '.$resolvidas.' pendencia(s)/duplicata(s) documentada(s) como resolvida(s). Restam '.count($restantes).' impedimento(s).'.($restantes?' Proxima correcao: '.$restantes[0]:' Pronto para a conferencia e decisao humana.');
            }
            if($acao==='criterio')
                return 'NOTA CONFIRMADA NO BANCO. Restam '.count($restantes).' impedimento(s).'.($restantes?' Proximo: '.$restantes[0]:' Pronto para a conferencia humana.');
            return 'Decisao registrada no banco. Restam '.count($restantes).' impedimento(s).';
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
