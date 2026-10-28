<?php
declare(strict_types=1);
/** Migra metadados do MAT Têmporis Lab v6 para STAGING do Observatório: leitura apenas na origem. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Apenas CLI');}
require __DIR__.'/../app/bootstrap.php';
$sourceDb='mat_temporis_lab';$apply=false;
foreach($argv as $arg){
    if(str_starts_with($arg,'--source-db='))$sourceDb=substr($arg,12);
    if($arg==='--apply')$apply=true;
}
if(!preg_match('/^[A-Za-z0-9_]+$/D',$sourceDb) || $sourceDb===cfg('db_name')){fwrite(STDERR,"Origem invalida ou igual ao banco destino.\n");exit(1);}
$dsn='mysql:host='.cfg('db_host').';port='.(int)cfg('db_port',3306).';dbname='.$sourceDb.';charset=utf8mb4';
try{
    $source=new PDO($dsn,(string)cfg('db_user'),(string)cfg('db_pass'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $cols=$source->query('SHOW COLUMNS FROM marcos')->fetchAll(PDO::FETCH_COLUMN);
    foreach(['id','nome','ano_ref'] as $key)if(!in_array($key,$cols,true))throw new RuntimeException("Esquema de origem nao e o Têmporis Lab v6 (coluna $key ausente).");
    $count=(int)$source->query('SELECT COUNT(*) FROM marcos')->fetchColumn();
    $max=(int)$source->query('SELECT COALESCE(MAX(id),0) FROM marcos')->fetchColumn();
    echo "Fonte: $sourceDb.marcos ($count linhas; ultimo ID $max)\n";
    echo "Destino: ".cfg('db_name').".import_itens (somente staging; nenhuma aprovacao)\n";
    echo "Modo: ".($apply?'APLICAR':'SIMULACAO')."\n";
    if(!$apply){echo "Para importar, realize backup e acrescente --apply.\n";exit(0);}
    $target=db();if(!$target)throw new RuntimeException('MySQL do Observatorio indisponivel.');
    $fingerprint=hash('sha256',"laboratorio-v6:$sourceDb:$count:$max:v6.2");
    $check=$target->prepare('SELECT id FROM import_lotes WHERE arquivo_sha256=?');$check->execute([$fingerprint]);
    if($check->fetchColumn())throw new RuntimeException('Esse conjunto de registros ja existe na area de preparacao.');
    $target->beginTransaction();
    try{
        $q=$target->prepare('INSERT INTO import_lotes(arquivo_nome,arquivo_sha256,total_itens) VALUES(?,?,?)');
        $q->execute(['LAB V6: '.$sourceDb.'.marcos',$fingerprint,$count]);$batch=(int)$target->lastInsertId();
        $ins=$target->prepare('INSERT INTO import_itens(lote_id,numero_linha,bruto_json,titulo,ano,categoria,descricao,fonte,status,erro) VALUES(?,?,?,?,?,?,?,?,?,?)');
        $cursor=$source->query('SELECT * FROM marcos ORDER BY id');
        $i=0;
        while($m=$cursor->fetch()){
            $i++;$name=trim((string)($m['nome']??''));$year=$m['ano_ref']??$m['ano_inicio']??null;
            $valid=$name!=='' && $year!==null && is_numeric($year) && $year>=-9999999 && $year<=9999999;
            $origin=(string)($m['origem']??'');
            $ins->execute([$batch,$i,json_encode($m,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_INVALID_UTF8_SUBSTITUTE),substr($name,0,250),$valid?(int)round((float)$year):null,substr((string)($m['categoria']??'Outros'),0,90),$m['descricao']??null,substr($origin,0,350),$valid?'pendente':'invalido',$valid?null:'Nome ou ano precisa de revisão']);
        }
        $target->commit();echo "Importacao em staging concluida: lote #$batch com $i registros. Base original inalterada.\n";
    }catch(Throwable $e){$target->rollBack();throw $e;}
}catch(Throwable $e){fwrite(STDERR,"Falha: ".$e->getMessage()."\n");exit(1);}
