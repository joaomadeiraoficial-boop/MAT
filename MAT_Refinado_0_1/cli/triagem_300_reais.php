<?php
declare(strict_types=1);
/** CLI idempotente, progresso persistente, somente leitura do MySQL. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/AtlasReais.php';
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
$opts=getopt('', ['modo:','limite::','refazer','ajuda']);
if(isset($opts['ajuda'])){
    echo "Uso: php cli/triagem_300_reais.php --modo=preparar|ia|resumo [--limite=5] [--refazer]\n";exit(0);
}
$mode=(string)($opts['modo']??'');
if(!in_array($mode,['preparar','ia','resumo'],true)){fwrite(STDERR,"Informe --modo=preparar, --modo=ia ou --modo=resumo\n");exit(2);}
$limit=max(1,min(300,(int)($opts['limite']??5)));
$pdo=db();
if(!$pdo){fwrite(STDERR,"ERRO: MySQL indisponivel. Ative Apache/MySQL e confira config.local.php\n");exit(2);}
try {
    $rows=TriagemLoteReal::fetch300($pdo);
    if(!$rows)throw new RuntimeException('Lote dos 300 nao localizado no banco. Importe o arquivo MAT_300_CANDIDATOS_PARA_IMPORTACAO.csv antes de executar.');
    $folder=TriagemLoteReal::directory(true);
    $total=count($rows);
    fwrite(STDERR,"MAT / $total candidatos REAIS no lote. Saida privada: $folder\n");
    if($mode==='preparar'){
        $new=0;
        foreach($rows as $r){
            $id=(int)$r['id'];
            if(TriagemLoteReal::load($id)!==null)continue;
            $metadata=TriagemLoteReal::metadata($r);
            $entry=['versao'=>TriagemLoteReal::VERSION,'id'=>$id,'lote_id'=>(int)$r['lote_id'],
                'id_extracao'=>$metadata['id_extracao'],'titulo'=>(string)$r['titulo'],
                'ano'=>$r['ano'],'categoria'=>(string)$r['categoria'],
                'origem'=>$r['fonte'],'pagina_livro'=>$metadata['pagina_livro'],
                'status_original'=>(string)$r['status'], 'triagem_regra'=>TriagemLoteReal::rules($r),
                'ia_status'=>'nao_analisado', 'proposta'=>null];
            TriagemLoteReal::save($id,$entry);$new++;
        }
        echo "[OK] $total candidatos inspecionados; $new novos arquivos de triagem. Nenhuma nota inventada.\n";
    } elseif($mode==='ia'){
        require_once dirname(__DIR__).'/app/AssistenteCientifico.php';
        if(!function_exists('curl_init'))throw new RuntimeException('Ative extension=curl no php.ini do XAMPP para usar Ollama.');
        $done=0;$skipped=0;$errors=0;
        foreach($rows as $r) {
            if($done >= $limit)break;
            if(($r['status']??'')!=='pendente')continue;
            $id=(int)$r['id'];
            $entry=TriagemLoteReal::load($id);
            if($entry===null){fwrite(STDERR,"Aviso: execute primeiro --modo=preparar\n");exit(2);}
            if(in_array(($entry['ia_status']??''),['concluido','erro'],true) && !isset($opts['refazer'])){$skipped++;continue;}
            if(!($entry['triagem_regra']['pode_enviar_ia']??false)){$skipped++;continue;}
            $done++;
            fwrite(STDERR,"[$done/$limit] Qwen analisando #$id (".substr((string)$r['titulo'],0,65).")...\n");
            try {
                $answer=AssistenteCientifico::queryOllama($r,[],'import');
                $entry['ia_status']='concluido';
                $entry['proposta']=TriagemLoteReal::proposal($answer);
                unset($entry['erro']);
                fwrite(STDERR," [OK] {$entry['proposta']['notas_com_citacao']}/11 notas com citacao textual localizada — NAO VALIDADO\n");
            }catch(Throwable $ex){
                $entry['ia_status']='erro';$entry['erro']=$ex->getMessage();$errors++;
                fwrite(STDERR," [ERRO] #$id: ".$ex->getMessage()."\n");
            }
            // Grava imediatamente para permitir interrupcao e retomada da fila.
            $entry['avaliado_em']=date(DATE_ATOM);
            TriagemLoteReal::save($id,$entry);
        }
        echo "MAT: $done candidato(s) tentados neste comando; $errors erro(s), $skipped ja analisados/sem trecho. Arquivos locais preservados.\n";
    }
    $state=TriagemLoteReal::state($rows);
    $state['versao']=TriagemLoteReal::VERSION;$state['origem_lote']=TriagemLoteReal::SOURCE_FILE;
    $state['saida_privada']=$folder;
    $summary=json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    file_put_contents($folder.DIRECTORY_SEPARATOR.'resumo.json',$summary,LOCK_EX);
    $csv=fopen($folder.DIRECTORY_SEPARATOR.'progresso.csv','wb');
    if($csv!==false){
        fputcsv($csv,['id','id_extracao','ano','era','titulo','pagina_livro','status_banco','status_ia','notas_com_citacao','peso_provisorio','observacao'], ';');
        foreach($rows as $r){
            $a=TriagemLoteReal::load((int)$r['id']);$s=$a['proposta']??[];
            $math=$s['peso_provisorio_nao_validado']??null;
            fputcsv($csv,[$r['id'],$a['id_extracao']??'', $r['ano'],AtlasReais::keyForYear($r['ano'])??'',
              $r['titulo'],$a['pagina_livro']??'',$r['status'],$a['ia_status']??'nao_preparado',$s['notas_com_citacao']??0,
              is_array($math)?number_format((float)$math['w'],8,'.',''):'',
              'PROPOSTA NAO VALIDADA - SEM INCLUSAO NA TEMPORIS'], ';');
        }
        fclose($csv);
    }
    // Planilha auditavel de notas propostas (fora do htdocs). Celulas vazias = sem evidencia.
    $evaluationCsv=fopen($folder.DIRECTORY_SEPARATOR.'propostas_11_criterios.csv','wb');
    if($evaluationCsv!==false){
        $headers=['id','ano','titulo','pagina_livro','estado_ia','total_notas_localizadas'];
        foreach(TriagemLoteReal::CRITERIA as $key){$headers[]=$key;$headers[]='citacao_'.$key;$headers[]='justificativa_'.$key;}
        $headers[]='revisado_pelo_pesquisador';$headers[]='observacoes_revisao';
        fputcsv($evaluationCsv,$headers,';');
        foreach($rows as $r){
            $info=TriagemLoteReal::load((int)$r['id']);
            $answer=$info['proposta']??[];
            $columns=[$r['id'],$r['ano'],$r['titulo'],$info['pagina_livro']??'', $info['ia_status']??'nao_preparado',$answer['notas_com_citacao']??0];
            foreach(TriagemLoteReal::CRITERIA as $field){
                $cell=$answer['criterios'][$field]??[];
                $columns[]=isset($cell['nota'])?$cell['nota']:'';
                $columns[]=$cell['citacao']??'';
                $columns[]=$cell['justificativa']??'';
            }
            $columns[]='NAO';$columns[]='';
            fputcsv($evaluationCsv,$columns,';');
        }
        fclose($evaluationCsv);
    }
    echo $summary."\n";
} catch(Throwable $e){fwrite(STDERR,'MAT TRIAGEM: '.$e->getMessage()."\n");exit(1);}
