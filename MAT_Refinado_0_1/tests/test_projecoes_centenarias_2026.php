<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TemporisProjecoes.php';
function verify(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);echo '[OK] '.$msg.PHP_EOL;}
$pts=[];
for($y=1800;$y<=2026;$y++) {
    $n=$y<1900?1:($y<2000?2:3);
    for($j=0;$j<$n;$j++)$pts[]=['id'=>'M'.$y.'-'.$j,'year'=>$y,'score'=>1.0];
}
$a=TemporisProjecoes::avaliar($pts,1900,2000,'log','media50',2026);
verify($a['status']==='comparavel','1900 -> 2000 comparável e tem observação');
verify($a['t_observed']!==null && $a['error_signed']!==null,'Erro com sinal definido');
verify(abs($a['t_predicted']-$a['t_observed']-$a['error_signed'])<1e-8,'Erro é previsto menos observado');
$b=TemporisProjecoes::avaliar($pts,1926,2026,'potencia','media100',2026);
verify($b['status']==='comparavel','1926 -> 2026 calculável na fórmula potência');
verify(abs(TemporisProjecoes::indice(50.0,50.0,'potencia')-100)<1e-8,'T(corte)=100 na potência');
verify(abs(TemporisProjecoes::indice(50.0,50.0,'log')-100)<1e-8,'T(corte)=100 no log');
$c=TemporisProjecoes::avaliar($pts,2026,2126,'log','tendencia100',2026);
verify($c['status']==='futuro_sem_observacao' && $c['t_predicted']!==null,'2026 -> 2126 prevê mas não inventa observação');
verify($c['error_signed']===null && $c['error_absolute']===null && $c['error_percent']===null,'2026 -> 2126 não apresenta erro fictício');
$leak=$pts;$leak[]=['year'=>2100,'score'=>99999999.0];
$d=TemporisProjecoes::avaliar($leak,2026,2126,'log','tendencia100',2026);
verify(abs($d['t_predicted']-$c['t_predicted'])<1e-9,'Eventos após asOf não contaminam a previsão');
$future=$pts;for($y=1927;$y<=2026;$y++)$future[]=['year'=>$y,'score'=>5000.];
$e=TemporisProjecoes::avaliar($future,1926,2026,'log','media50',2026);
$f=TemporisProjecoes::avaliar($pts,1926,2026,'log','media50',2026);
verify(abs($e['t_predicted']-$f['t_predicted'])<1e-9,'Eventos pós-corte não entram no TREINO');
verify(abs($e['t_observed']-$f['t_observed'])>1e-4,'Eventos pós-corte entram somente na observação do retroteste');
$met=TemporisProjecoes::metricasHistoricas($pts,2026,100,'log','media50',2026,20);
verify($met['n']>0 && $met['rmse']>=0,'Métricas históricas para horizonte centenário');
$fixed=TemporisProjecoes::avaliar($pts,2026,2126,'log','media50',2026,1900);
verify($fixed['reference_year']===1900 && $fixed['t_origin']>100.0,'Ano-base 1900 fixo mostra T(2026) > 100');
verify($fixed['t_predicted']>=$fixed['t_origin'],'Previsão 2126 não recomeça artificialmente em 100');
$historicFixed=TemporisProjecoes::avaliar($pts,1926,2026,'log','media50',2026,1900);
verify($historicFixed['t_observed']!==null && $historicFixed['t_observed']>100.0,'Retroteste compara T(1926) e T(2026) na mesma base');
verify(abs($fixed['t_origin']-$historicFixed['t_observed'])<1e-8,'Mesmo T(2026) observado nas duas simulações com base 1900');
$prior=TemporisProjecoes::metricasHistoricas($pts,2026,100,'log','media50',2026,20,1900);
verify($prior['n']>=1 && $prior['n']<=3,'Retrotestes com base fixa não usam cortes anteriores à base');
$insuf=TemporisProjecoes::avaliar([['year'=>1900,'score'=>1.]],1900,2000,'log','media50',2026);
verify($insuf['status']==='insuficiente','Sem inventar curva para amostra insuficiente');
$svg=TemporisProjecoes::grafico($a,$met);
verify(str_contains($svg,'<svg') && str_contains($svg,'2000'),'SVG cronológico é produzido');
$old=[['year'=>-200000,'score'=>1.]];
for($y=1700;$y<=2026;$y+=5)$old[]=['year'=>$y,'score'=>1.];
$met2=TemporisProjecoes::metricasHistoricas($old,2026,100,'log','media50',2026,20);
verify(is_array($met2) && $met2['n']>=0,'Datas pré-históricas não quebram os retrotestes');
try{TemporisProjecoes::avaliar($pts,2027,2127,'log','media50',2026);throw new RuntimeException('Era para falhar');}
catch(InvalidArgumentException $e){verify(true,'Não usa corte no futuro');}
