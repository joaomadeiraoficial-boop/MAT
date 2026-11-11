<?php
declare(strict_types=1);
/**
 * Painel MAT: leitura apenas. Mantem import_itens separados de marcos.
 * Os arquivos privados sao propostas, nunca aprovacao nem verificacao de fontes.
 */
final class PainelCientifico {
    public const VERSION = 'mat-painel-cientifico-1.0';

    public static function vazio(string $estado = 'BANCO_INDISPONIVEL'): array {
        return ['estado'=>$estado, 'total'=>null, 'trechos_suficientes'=>null,
            'trechos_pendentes'=>null, 'eventos_definidos'=>null,
            'aptos_pontuacao'=>null, 'propostas_11'=>null, 'duplicatas_suspeitas'=>null,
            'marcos_com_pendencias'=>null, 'marcos_com_nota_zero'=>null,
            'fila'=>[], 'contagem_acoes'=>[]];
    }

    /** Metodo puro/testavel; recebe apenas as linhas reais e propostas preexistentes. */
    public static function resumir(array $rows, string $privateDirectory): array {
        require_once __DIR__.'/TriagemLoteReal.php';
        require_once __DIR__.'/CuradoriaMarcos.php';
        require_once __DIR__.'/PontuadorGemini.php';
        require_once __DIR__.'/RevisedMethodology.php';
        $items=[];
        foreach ($rows as $row) {
            $id=(int)($row['id']??0);
            if($id<=0 || isset($items[$id])) continue;
            $historico=self::readJson($privateDirectory.DIRECTORY_SEPARATOR.'normalizacao_'.$id.'.json');
            $items[$id]=CuradoriaMarcos::resolve($row,$historico);
        }
        $links=CuradoriaMarcos::duplicateAlerts(array_values($items));
        $duplicates=[];
        foreach($items as $id=>$candidate) if(in_array('POSSIVEL_DUPLICATA',$candidate['alertas'],true))$duplicates[(int)$id]=true;
        foreach($links as $id=>$refs) foreach($refs as $related) {
            $duplicates[(int)$id]=true;
            $duplicates[(int)$related]=true;
        }
        $counts=['total'=>count($items),'trechos_suficientes'=>0,'trechos_pendentes'=>0,
            'eventos_definidos'=>0,'aptos_pontuacao'=>0,'propostas_11'=>0,
            'duplicatas_suspeitas'=>count($duplicates), 'marcos_com_pendencias'=>0,
            'marcos_com_nota_zero'=>0];
        $queue=[];$actions=[];
        foreach($items as $id=>$candidate){
            $enough=!in_array('TRECHO_INSUFICIENTE',$candidate['alertas'],true);
            if($enough)$counts['trechos_suficientes']++;else $counts['trechos_pendentes']++;
            $defined=$candidate['identificacao']!=='pendente';
            if($defined)$counts['eventos_definidos']++;
            $duplicate=isset($duplicates[$id]) || in_array('POSSIVEL_DUPLICATA',$candidate['alertas'],true);
            $ready=$candidate['apto_pontuacao_assistida'] && !$duplicate;
            if($ready)$counts['aptos_pontuacao']++;
            $saved=self::readJson($privateDirectory.DIRECTORY_SEPARATOR.'pontuacao_gemini_'.$id.'.json');
            $full=false;$pending=[];$zero=false;
            if($ready && is_array($saved) && ($saved['estado']??'')==='PROPOSTA_NAO_VALIDADA'){
                $model=(string)($saved['modelo']??'');
                $fingerprint=CuradoriaMarcos::fingerprint($candidate,$model,
                    RevisedMethodology::VERSION.'|'.PontuadorGemini::VERSION);
                $cacheValido=hash_equals($fingerprint,(string)($saved['fingerprint']??''));
                if($cacheValido){
                    $review=PontuadorGemini::pendingFromSaved($saved);
                    $pending=$review['pendencias'];
                    $zero=($review['notas_zero']??0)>0;
                    if($pending)$counts['marcos_com_pendencias']++;
                    if($zero)$counts['marcos_com_nota_zero']++;
                }
                $full=$cacheValido && !$zero
                    && (int)($saved['proposta']['criterios_com_citacao']??0)===11
                    && is_array($saved['proposta']['componentes_hipoteticos_nao_oficiais']??null);
            }
            if($full) $counts['propostas_11']++;
            if(!$enough) $action='COMPLETAR_TRECHO';
            elseif(!$defined) $action='IDENTIFICAR_EVENTO';
            elseif(in_array('DATA_PENDENTE',$candidate['alertas'],true) || in_array('ANO_EXTRAIDO_DIVERGENTE',$candidate['alertas'],true)) $action='CONFERIR_DATA';
            elseif(in_array('FONTE_AUSENTE',$candidate['alertas'],true)) $action='COMPLETAR_FONTE';
            elseif($duplicate) $action='REVISAR_DUPLICATA';
            elseif(in_array('APROFUNDAR_DEFINICAO',$candidate['alertas'],true)) $action='IDENTIFICAR_EVENTO';
            elseif($zero) $action='REVISAR_CRITERIO_NAO_ATENDIDO';
            elseif($pending) $action='COMPLETAR_EVIDENCIAS_DAS_NOTAS';
            elseif($full) $action='AUDITAR_NOTAS_E_FONTES';
            elseif($ready) $action='PONTUAR_COM_GEMINI';
            else $action='REVISAR_EVIDENCIAS';
            $actions[$action]=($actions[$action]??0)+1;
            $rank=['AUDITAR_NOTAS_E_FONTES'=>0,
                'REVISAR_CRITERIO_NAO_ATENDIDO'=>1,'COMPLETAR_EVIDENCIAS_DAS_NOTAS'=>2,
                'PONTUAR_COM_GEMINI'=>3,
                'REVISAR_DUPLICATA'=>4,'COMPLETAR_FONTE'=>5,'CONFERIR_DATA'=>6,
                'IDENTIFICAR_EVENTO'=>7,'COMPLETAR_TRECHO'=>8,'REVISAR_EVIDENCIAS'=>9][$action];
            $queue[]=['id'=>$id,'titulo'=>(string)$candidate['titulo_candidato'],
                'ano'=>$candidate['ano'],'acao'=>$action,'prioridade'=>$rank,
                'identificacao'=>$candidate['identificacao'], 'trecho_caracteres'=>strlen((string)$candidate['trecho']),
                'fonte_declarada'=>(string)$candidate['fonte_declarada'],
                'alertas'=>$candidate['alertas'], 'duplicata'=>$duplicate,
                'avaliacao'=>'NAO_APROVADA', 'proposta_11'=>$full,
                'pendencias_total'=>count($pending),
                'pendencia_resumo'=>$pending ? (($pending[0]['rotulo']??'').' — '.($pending[0]['o_que_falta']??'')) :
                    ($candidate['alertas'] ? implode('; ',array_slice($candidate['alertas'],0,2)) :
                        ($full?'Revisar citacoes e conferir fontes externas.':'Aguardando avaliacao pelas 11 rubricas.'))];
        }
        usort($queue, static fn(array $a,array $b):int => [$a['prioridade'],$a['id']] <=> [$b['prioridade'],$b['id']]);
        return ['estado'=>'OK']+$counts+['fila'=>$queue,'contagem_acoes'=>$actions,
            'aviso'=>'Fila de trabalho e propostas, sem validacao historica independente ou inclusao oficial.'];
    }

    public static function consultar(?PDO $pdo):array {
        if($pdo===null)return self::vazio();
        try{
            require_once __DIR__.'/TriagemLoteReal.php';
            return self::resumir(TriagemLoteReal::fetch300($pdo),TriagemLoteReal::directory(false));
        } catch(Throwable $e) {
            error_log('MAT: painel cientifico indisponivel: '.get_class($e));
            return self::vazio('CONSULTA_INDISPONIVEL');
        }
    }

    private static function readJson(string $file):?array {
        if(!is_file($file))return null;
        $raw=@file_get_contents($file);
        if(!is_string($raw))return null;
        $obj=json_decode($raw,true);
        return is_array($obj)?$obj:null;
    }
}
