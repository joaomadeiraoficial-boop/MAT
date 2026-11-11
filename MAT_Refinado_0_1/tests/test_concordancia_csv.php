<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/ConcordanciaAvaliadores.php';
function confCsv(bool $v,string $m): void {if(!$v)throw new RuntimeException('FALHA: '.$m);echo '[OK] '.$m."\n";}
$dir=sys_get_temp_dir().'/mat_concordancia_'.bin2hex(random_bytes(5));
mkdir($dir);
try{
  $cols=array_merge(['id_extracao'],array_keys(CuradoriaRodadas::CRITERIOS));
  $a=$dir.'/a.csv';$b=$dir.'/b.csv';
  foreach([$a,$b] as $which=>$file){
    $f=fopen($file,'wb');fputcsv($f,$cols,';','"','');
    fputcsv($f,array_merge(['MCD-00001'],array_fill(0,11,'0')),';','"','');
    fputcsv($f,array_merge(['MCD-00002'],array_fill(0,11,$which===0?'3':'2')),';','"','');
    fclose($f);
  }
  $pa=ConcordanciaAvaliadores::carregarCsv($a);
  $pb=ConcordanciaAvaliadores::carregarCsv($b);
  $result=ConcordanciaAvaliadores::analisar($pa,$pb);
  confCsv($result['dossies_comuns']===2,'Pareamento de dossiês independente da ordem');
  confCsv($result['criterios']['impacto_difusao']['n']===2,'Análise por critério trabalha com pares');
  confCsv($result['geral_descritivo']['n']===22,'Resumo contabiliza 11 critérios por 2 candidatos');
  confCsv($result['geral_descritivo']['concordancia_exata']===0.5,'Concordância exata refletiu um caso igual e outro divergente');
  $f=fopen($b,'ab');fputcsv($f,array_merge(['MCD-00001'],array_fill(0,11,'2')),';','"','');fclose($f);
  try{ConcordanciaAvaliadores::carregarCsv($b);throw new RuntimeException('CSV duplicado passou');}
  catch(InvalidArgumentException $e){confCsv(true,'Duplicação do mesmo ID é rejeitada');}
}finally{
  foreach([$dir.'/a.csv',$dir.'/b.csv'] as $f)if(is_file($f))unlink($f);
  rmdir($dir);
}
