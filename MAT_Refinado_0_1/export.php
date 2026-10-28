<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$format=(string)($_GET['format']??'csv');
if(!in_array($format,['csv','json','sql'],true)){http_response_code(400);exit('Formato invalido.');}
$rows=all_markers();
$fields=['id','titulo','ano','ano_fim','periodo','categoria','descricao','local','fonte','referencia_url','criterio_inclusao','impacto','confiabilidade','fator_fundacional','status'];
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="mat_acervo_'.date('Ymd').'.'.$format.'"');
if($format==='json'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['aviso'=>'Registros de demonstração ou sem revisão não são evidências validadas.','gerado_em'=>date(DATE_ATOM),'rows'=>$rows],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if($format==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    $out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$fields,';', '"', '');
    foreach($rows as $row){$vals=[];foreach($fields as $key){$cell=(string)($row[$key]??'');$vals[]=preg_match('/^[=+@\-]/u',$cell) ? "'".$cell : $cell;}fputcsv($out,$vals,';','"','');}
    fclose($out);exit;
}
header('Content-Type: text/plain; charset=utf-8');
echo "-- MAT: exportacao de dados para consulta/backup leve\n-- Nao inclui usuarios, senhas nem fontes completas.\n-- Para restauracao integral, use backup do MySQL.\nUSE mat_observatorio;\n";
foreach($rows as $row){$cols=[];$vals=[];foreach($fields as $field){if($field==='id')continue;$cols[]='`'.$field.'`';$value=$row[$field]??null;$vals[]=$value===null?'NULL':"'".str_replace(["\\","'"],["\\\\","''"],(string)$value)."'";}echo 'INSERT INTO marcos('.implode(',',$cols).') VALUES('.implode(',',$vals).');' ."\n";}
