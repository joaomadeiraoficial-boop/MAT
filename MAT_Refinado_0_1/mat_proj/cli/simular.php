<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Uso somente pelo terminal');}
require dirname(__DIR__).'/engine.php';
$path=$argv[1]??dirname(__DIR__).'/data/EXEMPLO_SINTETICO.csv';
$year=isset($argv[2])?(int)$argv[2]:2022;
$kind=$argv[3]??'reconstruct';
try{
 $data=mp_load_csv($path);
 $r=$kind==='forecast'?mp_forecast($data,$year):mp_reconstruct($data,$year,20,'stratified',42);
 $compact=array_diff_key($r,['details'=>true]);
 echo json_encode($compact,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."\n";
}catch(Throwable $e){fwrite(STDERR,"ERRO: {$e->getMessage()}\n");exit(1);}
