<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once dirname(__DIR__).'/app/bootstrap.php';
foreach(['TriagemLoteReal','AuditoriaAmbiguidade','NormalizacaoCandidatos'] as $c)require_once dirname(__DIR__).'/app/'.$c.'.php';
$args=getopt('',['modo:','limite:','modelo:','provedor:','refazer']);
$mode=(string)($args['modo']??'diagnostico');
if(!in_array($mode,['diagnostico','api'],true)){fwrite(STDERR,"Modo use: diagnostico|api\n");exit(2);}
$limit=max(1,min(300,(int)($args['limite']??3)));
$model=(string)($args['modelo']??'gemini-2.5-flash-lite');
$provider=(string)($args['provedor']??'gemini');
if($provider!=='gemini'){fwrite(STDERR,"Somente Gemini autorizado neste fluxo gratuito.\n");exit(2);}
if($mode==='api' && trim((string)getenv('MAT_GEMINI_API_KEY'))===''){
 fwrite(STDERR,"ERRO: configure MAT_GEMINI_API_KEY. Nada sera enviado.\n");exit(2);
}
$pdo=db();if(!$pdo){fwrite(STDERR,"MySQL indisponivel\n");exit(2);}
try {
 $rows=TriagemLoteReal::fetch300($pdo);
 if(!$rows)throw new RuntimeException('Lote 300 ausente');
 $folder=TriagemLoteReal::directory(true);
 $stats=['total'=>count($rows),'elegiveis_identificacao'=>0,'pendentes_de_dados'=>0,'requisicoes_nesta_execucao'=>0,'identificacoes_uteis_nesta_execucao'=>0,'erros_nesta_execucao'=>0,'resultados_salvos'=>0];
 $lines=[];
 $stopOnHttpError=false;
 foreach($rows as $row){
  $id=(int)$row['id'];$triage=NormalizacaoCandidatos::classify($row);
  $eligible=$triage['elegivel_identificacao'];$stats[$eligible?'elegiveis_identificacao':'pendentes_de_dados']++;
  $file=$folder.DIRECTORY_SEPARATOR.'normalizacao_'.$id.'.json';
  $previous=is_file($file)?json_decode((string)file_get_contents($file),true):null;
  $entry=is_array($previous)?$previous:null;
  if($mode==='api' && !$stopOnHttpError && $eligible && $stats['requisicoes_nesta_execucao']<$limit &&
     (isset($args['refazer'])||!is_array($entry)||($entry['estado']??'')==='erro')){
    $stats['requisicoes_nesta_execucao']++;
    try {
      $data=NormalizacaoCandidatos::normalize($row,$model,$provider);
      $entry=['versao'=>NormalizacaoCandidatos::VERSION,'id'=>$id,'estado'=>'proposta_nao_validada',
        'proposta'=>$data['proposta'],'modelo'=>$data['modelo'],'provedor'=>'gemini','uso'=>$data['uso'],
        'data_execucao'=>date(DATE_ATOM),'revisao_humana'=>false];
      if($data['proposta']['identificacao_util'])$stats['identificacoes_uteis_nesta_execucao']++;
    }catch(Throwable $e){$stats['erros_nesta_execucao']++;
      $entry=['versao'=>NormalizacaoCandidatos::VERSION,'id'=>$id,'estado'=>'erro','erro'=>$e->getMessage()];
      fwrite(STDERR,"Falha #$id: ".$e->getMessage()."\n");
      if(preg_match('/Gemini HTTP (400|401|402|403|404|429)\b/',$e->getMessage())){
        $stopOnHttpError=true;
        fwrite(STDERR,"Lote interrompido: corrija o problema antes de gastar novas requisicoes.\n");
      }
    }
    $tmp=$file.'.tmp';file_put_contents($tmp,json_encode($entry,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);
    if(!rename($tmp,$file))throw new RuntimeException('Falha ao salvar '.$file);
  }
  if(is_array($entry))$stats['resultados_salvos']++;
  $lines[]=[$id,(string)($row['ano']??''),(string)($row['titulo']??''),$eligible?'IDENTIFICACAO_POSSIVEL':'DADOS_INSUFICIENTES',
     (string)($entry['estado']??'pendente'),(string)($entry['proposta']['tipo']??''),
     (string)($entry['proposta']['titulo_proposto']??''),
     !empty($entry['proposta']['citacao_encontrada'])?'SIM':'NAO','NAO_APROVADO'];
 }
 $file=$folder.DIRECTORY_SEPARATOR.'normalizacao_relatorio.csv';
 $fp=fopen($file,'wb');if(!$fp)throw new RuntimeException('Nao abriu CSV');
 fputcsv($fp,['id','ano_original','titulo_original','triagem','estado','tipo_sugerido','titulo_proposto','citacao_localizada','aprovacao'],';');
 foreach($lines as $line)fputcsv($fp,$line,';');fclose($fp);
 echo json_encode(['status'=>'OK','relatorio'=>$file,'contagens'=>$stats,
 'aviso'=>'Identificacao de evento nao e pesquisa independente, validacao de fonte ou pontuacao da Temporis.'],
 JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,"ERRO: ".$e->getMessage()."\n");exit(1);}
