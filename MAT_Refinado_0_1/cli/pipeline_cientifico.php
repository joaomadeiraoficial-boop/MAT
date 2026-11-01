<?php
declare(strict_types=1);
/** CLI para processar candidatos sem alterar o acervo oficial ou as formulas. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once dirname(__DIR__).'/app/bootstrap.php';
foreach(['TriagemLoteReal','AuditoriaAmbiguidade','AssistenteCientifico','AvaliacaoNuvem'] as $name)require_once dirname(__DIR__).'/app/'.$name.'.php';
$args=getopt('',['modo:','limite::','modelo::','refazer','ajuda']);
if(isset($args['ajuda'])){echo "Uso: php cli/pipeline_cientifico.php --modo=diagnostico|api|exportar [--limite=5] [--modelo=gpt-4.1-mini] [--refazer]\n";exit;}
$mode=(string)($args['modo']??'diagnostico');
if(!in_array($mode,['diagnostico','api','exportar'],true)){fwrite(STDERR,"Modo invalido\n");exit(2);}
$limit=max(1,min(300,(int)($args['limite']??5)));
$model=(string)($args['modelo']??'gpt-4.1-mini');
$pdo=db();if(!$pdo){fwrite(STDERR,"MySQL indisponivel; inicie o MySQL no XAMPP\n");exit(2);}
try {
    $rows=TriagemLoteReal::fetch300($pdo);
    if(!$rows)throw new RuntimeException('Lote 300 nao localizado');
    $folder=TriagemLoteReal::directory(true);
    $counts=['total'=>count($rows),'bloqueados'=>0,'analise_possivel'=>0,'processados_api'=>0,'propostas_11'=>0,'erros'=>0];
    $out=[];$sent=0;
    foreach($rows as $row){
        $id=(int)$row['id'];$assess=AvaliacaoNuvem::classify($row);
        $eligible=$assess['elegivel_para_analise'];
        $counts[$eligible?'analise_possivel':'bloqueados']++;
        $entry=TriagemLoteReal::load($id)??[
           'versao'=>TriagemLoteReal::VERSION,'id'=>$id,'titulo'=>(string)($row['titulo']??''),
           'ano'=>$row['ano'],'ia_status'=>'nao_analisado','proposta'=>null];
        $entry['auditoria_automatica']=$assess;
        if($mode==='api' && $eligible && $sent<$limit &&
          (isset($args['refazer'])||!in_array(($entry['ia_status']??''),['concluido','erro'],true))){
            $sent++;fwrite(STDERR,"Avaliando #$id ($sent/$limit)\n");
            try{
                $result=AvaliacaoNuvem::request($row,$model);
                $entry['proposta']=TriagemLoteReal::proposal($result['verificado']);
                $entry['ia_status']='concluido';$entry['ia_origem']='openai_api';
                $entry['ia_modelo']=$result['model'];$entry['tokens']=$result['usage'];
                $entry['avaliado_em']=date(DATE_ATOM);unset($entry['erro']);
                $counts['processados_api']++;
            }catch(Throwable $e){
                $entry['ia_status']='erro';$entry['erro']=$e->getMessage();$counts['erros']++;
                fwrite(STDERR,"Falha #$id: ".$e->getMessage()."\n");
            }
        }
        if($mode==='api' || $mode==='diagnostico')TriagemLoteReal::save($id,$entry);
        if(($entry['proposta']['notas_com_citacao']??0)===11)$counts['propostas_11']++;
        $out[]=[(string)$id,(string)($row['ano']??''),(string)($row['titulo']??''),
           $assess['situacao'],AvaliacaoNuvem::rowStatus($entry),
           (string)($entry['proposta']['notas_com_citacao']??0),
           implode('|',array_map(static fn($r)=>$r['codigo'],$assess['riscos'])),
           (string)($entry['ia_modelo']??''),'NAO_VALIDADO'];
    }
    $file=$folder.DIRECTORY_SEPARATOR.'pipeline_relatorio.csv';
    $csv=fopen($file,'wb');if(!$csv)throw new RuntimeException('Falha escrita CSV');
    fputcsv($csv,['id','ano','titulo','auditoria','estado_proposta','notas_com_citacao','riscos','modelo','situacao_cientifica'],';');
    foreach($out as $line)fputcsv($csv,$line,';');fclose($csv);
    echo json_encode(['status'=>'OK','arquivo'=>$file,'contagens'=>$counts,
      'observacao'=>'Propostas nao aprovadas. Nao houve pesquisa externa nem validacao independente de fontes. Nenhum marco entrou na Temporis oficial.'],
      JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,"PIPELINE: ".$e->getMessage()."\n");exit(1);}
