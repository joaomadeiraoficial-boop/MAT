<?php
declare(strict_types=1);
require __DIR__.'/../engine.php';
function ok(bool $yes,string $what): void {if(!$yes)throw new RuntimeException('FALHOU: '.$what);echo "[OK] {$what}\n";}
$d=mp_load_csv(__DIR__.'/../data/EXEMPLO_SINTETICO.csv');
ok(count($d)>=80,'CSV de demonstração carrega');
$a=mp_reconstruct($d,2022,20,'stratified',42);
ok($a['observed']>=1 && $a['hidden']>0,'Separação observado/oculto');
ok(abs($a['pred_mean']-mp_reconstruct($d,2022,20,'stratified',42)['pred_mean'])<1e-9,'Aleatoriedade reprodutível');
$obs=[];foreach($a['details'] as $row)if($row['observed'])$obs[$row['code']]=true;
$d2=$d;foreach($d2 as $k=>&$r)if(!isset($obs[$k]) && isset($r['years'][2022]))$r['years'][2022]=mp_limit($r['years'][2022]+10);unset($r);
$b=mp_reconstruct($d2,2022,20,'stratified',42);
foreach($a['details'] as $i=>$r){if(abs($r['predicted']-$b['details'][$i]['predicted'])>=1e-9)throw new RuntimeException('Vazamento na unidade '.$i);}
ok(true,'Nenhuma unidade oculta alterou a previsão após mudança da verdade');
$fc=mp_forecast($d,2022);$d3=$d;foreach($d3 as &$r)if(isset($r['years'][2022]))$r['years'][2022]=mp_limit($r['years'][2022]+10);unset($r);
$fc2=mp_forecast($d3,2022);ok(abs($fc['pred_mean']-$fc2['pred_mean'])<1e-9,'Sem vazamento na previsão do ano-alvo');
ok($fc['mae']!==null && $fc['baseline_mae']!==null,'Previsão com comparação ingênua');
$bt=mp_backtest($d);ok(count($bt)>4,'Retrospectivas com múltiplos anos');
$bad=false;try{mp_reconstruct($d,2022,99);}catch(InvalidArgumentException $e){$bad=true;}
ok($bad,'Rejeita fração inválida');
$bad=false;try{mp_load_csv(__DIR__.'/../engine.php');}catch(RuntimeException $e){$bad=true;}
ok($bad,'Rejeita CSV malformado');
echo "MAT-PROJ: TESTES APROVADOS\n";
