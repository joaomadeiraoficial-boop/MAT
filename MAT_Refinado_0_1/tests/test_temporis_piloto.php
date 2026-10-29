<?php
declare(strict_types=1);
require __DIR__.'/../app/TemporisPiloto.php';
function must(bool $yes,string $msg):void {if(!$yes)throw new RuntimeException('FALHOU: '.$msg);echo '[OK] '.$msg."\n";}
must(class_exists('RevisedMethodology'), 'Metodologia revisada carregada sem dependencia do bootstrap');
must(count(RevisedMethodology::ALL)===11, 'As 11 rubricas revisadas estao disponiveis');
$rows=TemporisPiloto::examples();
$a=TemporisPiloto::series($rows,1971,2);
must(count($a['points'])===6,'Ensaio tem seis marcos ordenados cronologicamente');
must($a['points'][0]['year']===1712 && $a['points'][5]['year']===2012,'Ordenacao cronologica');
$base=null;
foreach($a['points'] as $p) if($p['year']===1971)$base=$p;
must($base!==null && abs($base['t']-100.)<1e-10,'Indice normalizado em T(1971)=100');
must($a['t_latest']>100,'Serie do ensaio apos o ano-base');
$expected=TemporisPiloto::calc([3,3,3,3, 3,3,3,3, 3,3,3]);
must(abs($expected['I']-10)<1e-12 && abs($expected['C']-1)<1e-12 && abs($expected['F']-1.5)<1e-12 && abs($expected['w']-1.5)<1e-12,'Faixa maxima I=10 C=1 F=1,5 w=1,5');
try {TemporisPiloto::calc(array_fill(0,10,3));throw new RuntimeException('Falhou sem nota');} catch(InvalidArgumentException $e) {must(true,'Onze notas obrigatorias');}
// Paridade numerica contra a implementacao OFICIAL do indice revisado, apenas nos dados falsos de teste.
$fakeMarkers=[];$fakeRatings=[];
foreach($rows as $i=>$row){
    $id=$i+10000;
    $fakeMarkers[]=['id'=>$id,'titulo'=>$row['title'],'ano'=>$row['year'],'status'=>'aprovado','incluir_temporis'=>1,'elegibilidade_temporis'=>'sim','escopo_revisado'=>1,'fonte_vinculada'=>1];
    $ev=['estado'=>'confirmada'];
    foreach(TemporisPiloto::FIELDS as $j=>$field){$ev[$field]=$row['scores'][$j];$ev['evidencia_'.$field]='APENAS DADO SINTETICO DE TESTE - NAO EVIDENCIA';}
    $fakeRatings[$id]=$ev;
}
$official=RevisedMethodology::series($fakeMarkers,$fakeRatings,1971,2);
must($official['included']===6 && abs($a['k_base']-$official['k_base'])<1e-10 && abs($a['t_latest']-$official['t_latest'])<1e-10,'Paridade com RevisedMethodology::series original');
foreach([1.,1.5,2.] as $beta) {
    $demo=TemporisPiloto::series($rows,1971,$beta);
    $official=RevisedMethodology::series($fakeMarkers,$fakeRatings,1971,$beta);
    must(abs($demo['t_latest']-$official['t_latest'])<1e-10,'Paridade para beta='.$beta);
}
echo "MAT: ENSAIO DIDATICO TESTADO; SEM MODIFICAR BANCO OU APROVAR MARCOS.\n";
