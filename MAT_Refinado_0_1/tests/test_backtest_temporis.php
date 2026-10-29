<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TemporisBacktest.php';
function check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException('FALHOU: '.$label);echo '[OK] '.$label."\n";}
$points=TemporisBacktest::simulatedPoints();
check(count($points)>100,'Simulação possui histórico suficiente');
$a=TemporisBacktest::experiment($points,1920,2020);
check($a['status']==='comparavel' && $a['k_origin']>0 && $a['k_observed']>$a['k_origin'],'Observado e referência construídos');
check(abs(100*log1p($a['k_observed'])/log1p($a['k_origin'])-$a['t_observed'])<1e-9,'Fórmula logarítmica oficial');
check(($a['models']['media50']['absolute_error']??null)!==null,'Previsão e erro calculados');
$changed=$points;
$changed[]=['year'=>2010,'weight'=>123456.0];
$b=TemporisBacktest::experiment($changed,1920,2020);
check(abs($a['models']['media50']['k_predicted']-$b['models']['media50']['k_predicted'])<1e-9,'NENHUM vazamento: futuro não altera a previsão');
check(abs($a['t_observed']-$b['t_observed'])>0.001,'Futuro modifica apenas o observado');
$cut=TemporisBacktest::experiment(array_values(array_filter($points,fn($x)=>$x['year']<=1920)),1920,2020);
check($cut['t_observed']===null && $cut['status']==='sem_referencia_observada','Sem dados pós-corte não inventa erro');
$folds=TemporisBacktest::historicalBacktests($points,1920,100,20);
check(count($folds)>0,'Backtests históricos anteriores ao ano de corte');
check(max(array_column($folds,'target'))<=1920,'Nenhum alvo de validação posterior a 1920');
$metrics=TemporisBacktest::summarize($folds);
check(($metrics['media50']['n_folds']??0)>0 && $metrics['media50']['rmse']!==null,'MAE e RMSE para folds');
$insufficient=TemporisBacktest::experiment([['year'=>1900,'weight'=>1.0]],1920,2020);
check($insufficient['status']==='insuficiente' && $insufficient['models']===[],'Não inventa previsões com poucos dados');
// Mesmo o modo real depende da aprovação segundo os 11 critérios oficiais.
$m=['id'=>1,'ano'=>1900,'titulo'=>'Teste','status'=>'pendente','incluir_temporis'=>0,'elegibilidade_temporis'=>'revisar','escopo_revisado'=>0,'fonte_vinculada'=>0];
$real=TemporisBacktest::approvedPoints([$m],[],2020);
check($real['included']===0 && count($real['points'])===0,'Não converte candidato pendente em marco científico');
echo "MAT BACKTEST: TODOS OS TESTES APROVADOS (SEM MYSQL E SEM OLLAMA).\n";
