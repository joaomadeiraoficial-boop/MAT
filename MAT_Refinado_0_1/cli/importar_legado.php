<?php
declare(strict_types=1);
/** Importacao conservadora: SOMENTE leitura da base anterior e gravacao em staging do MAT novo. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Apenas CLI');}
require __DIR__.'/../app/bootstrap.php';
if(!db()){fwrite(STDERR,"Banco MAT novo indisponivel.\n");exit(1);}
$sourceDb='mat_v5';
foreach($argv as $arg){if(str_starts_with($arg,'--source-db='))$sourceDb=substr($arg,12);}
if(!preg_match('/^[A-Za-z0-9_]+$/',$sourceDb)){fwrite(STDERR,"Nome de banco invalido.\n");exit(1);}
try{
 $source=new PDO('mysql:host='.cfg('db_host').';port='.(int)cfg('db_port',3306).';dbname='.$sourceDb.';charset=utf8mb4',cfg('db_user'),cfg('db_pass'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $tables=$source->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
 $found=null;
 foreach(['marcos','marcos_historicos','marcos_tecnologicos','eventos'] as $candidate)if(in_array($candidate,$tables,true)){$found=$candidate;break;}
 if(!$found){fwrite(STDERR,"Tabela de marcos nao identificada. Tabelas disponiveis: ".implode(',',$tables)."\n");exit(1);}
 $rows=$source->query('SELECT * FROM `'.$found.'`')->fetchAll();
 $key=hash('sha256','legado:'.$sourceDb.':'.$found.':'.count($rows));
 $check=db()->prepare('SELECT id FROM import_lotes WHERE arquivo_sha256=?');$check->execute([$key]);
 if($check->fetchColumn()){fwrite(STDERR,"Este lote legado ja foi importado. Verifique a estacao de importacao.\n");exit(1);}
 $aliases=['titulo'=>['titulo','nome','nome_marco','marco','titulo_marco'],'ano'=>['ano','ano_inicio','data_ano','year'],
  'categoria'=>['categoria','area','tipo'],'descricao'=>['descricao','resumo','texto','detalhes'],
  'fonte'=>['fonte','referencia','fonte_historica']];
 $pick=static function(array $row,array $aliases): string {foreach($aliases as $key)if(array_key_exists($key,$row) && $row[$key]!==null)return trim((string)$row[$key]);return '';};
 db()->beginTransaction();
 $stmt=db()->prepare('INSERT INTO import_lotes (arquivo_nome,arquivo_sha256,total_itens) VALUES(?,?,?)');
 $stmt->execute(['LEGADO: '.$sourceDb.'.'.$found,$key,count($rows)]);$batch=(int)db()->lastInsertId();
 $insert=db()->prepare('INSERT INTO import_itens(lote_id,numero_linha,bruto_json,titulo,ano,categoria,descricao,fonte,status,erro) VALUES(?,?,?,?,?,?,?,?,?,?)');
 foreach($rows as $index=>$row){
  $title=$pick($row,$aliases['titulo']);$year=$pick($row,$aliases['ano']);$valid=($title!==''&&preg_match('/^-?\d{1,7}$/',$year));
  $insert->execute([$batch,$index+1,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),substr($title,0,250),$valid?(int)$year:null,substr($pick($row,$aliases['categoria'])?:'Outros',0,90),$pick($row,$aliases['descricao']),substr($pick($row,$aliases['fonte']),0,350),$valid?'pendente':'invalido',$valid?null:'Revisar nome e ano originais']);
 }
 db()->commit();
 echo "Importacao preservada em staging: lote #$batch / ".count($rows)." linhas. Banco original nao alterado.\n";
} catch(Throwable $e){if(db()->inTransaction())db()->rollBack();fwrite(STDERR,"Falha na importacao: ".$e->getMessage()."\n");exit(1);}
