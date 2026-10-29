<?php
/** Explicit, manual updater. Never executes on a normal page view. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Uso somente pelo terminal');}
require dirname(__DIR__).'/engine.php';
$url='https://ourworldindata.org/grapher/share-of-individuals-using-the-internet.csv?v=1&csvType=full&useColumnShortNames=true';
$target=dirname(__DIR__).'/data/owid_internet.csv';
if(isset($argv[1])) {
    if(!is_file($argv[1])){fwrite(STDERR,"Arquivo não encontrado: {$argv[1]}\n");exit(1);}
    $body=file_get_contents($argv[1]);
} else {
    echo "Buscando dados públicos da Our World in Data...\n";
    if(function_exists('curl_init')){
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>25,CURLOPT_USERAGENT=>'MAT-Proj educational data fetch/0.1',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $body=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        if($body===false || $status!==200){fwrite(STDERR,"Falha HTTP {$status}: {$err}\nBaixe o CSV no navegador e passe o caminho como argumento.\n");exit(1);}
    } else {
        $ctx=stream_context_create(['http'=>['timeout'=>25,'header'=>"User-Agent: MAT-Proj educational data fetch/0.1\r\n"]]);$body=@file_get_contents($url,false,$ctx);
        if($body===false){fwrite(STDERR,"Não foi possível acessar a fonte. Baixe o CSV manualmente.\n");exit(1);}
    }
}
if(strlen($body)>12000000 || strlen($body)<1000){fwrite(STDERR,"CSV rejeitado: tamanho inesperado\n");exit(1);}
$tmp=tempnam(sys_get_temp_dir(),'matproj_');file_put_contents($tmp,$body);
try{
    $countries=mp_load_csv($tmp);$years=mp_years($countries);
    if(count($countries)<50 || count($years)<10) throw new RuntimeException('Cobertura de países/anos insuficiente para substituir a base');
    if(!copy($tmp,$target))throw new RuntimeException('Não foi possível salvar CSV no diretório de dados');
    file_put_contents(dirname(__DIR__).'/data/owid_metadata_local.txt',"Fonte: {$url}\nBaixado: ".date('c')."\nSHA256: ".hash_file('sha256',$target)."\nPaíses: ".count($countries)."\n");
    echo "[OK] Dados reais instalados: ".count($countries)." países; ".count($years)." anos.\n";
    echo "[OK] SHA256: ".hash_file('sha256',$target)."\n";
}catch(Throwable $e){fwrite(STDERR,"Arquivo rejeitado: {$e->getMessage()}\n");exit(1);}finally{@unlink($tmp);}
