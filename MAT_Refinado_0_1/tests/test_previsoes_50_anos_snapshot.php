<?php
/** Regressão científica: recortes fixos na aba de Previsões (sem MySQL). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Somente CLI\n"); }
$root=dirname(__DIR__);
$configFile=$root.'/config.local.php';
if(!is_file($configFile)) $configFile=$root.'/config.example.php';
$config=is_file($configFile)?require $configFile:[];
if(!is_array($config))throw new RuntimeException('Configuração inválida');
if(!function_exists('cfg')) {
    function cfg(string $key,mixed $default=null):mixed { global $config;return $config[$key]??$default; }
}
require_once $root.'/app/TemporisMetodologia2026.php';
$source=TemporisFinal::obterDados('snapshot',2026);
$pts=TemporisFinal::filtrar($source['dados']['pontos'],'ampla');
if(count($pts)!==667)throw new RuntimeException('A base histórica do snapshot foi alterada: esperados 667 marcos');
$line=TemporisFinal::linha($pts,'quadratica');
$expected=[
    1850 => [100.74089519629061,99.51453125,5.921306657079303,26.28187465833755],
    1875 => [114.63841744783144,116.33267361111113,6.078898986866943,31.50645196394816],
    1900 => [125.66881561995719,125.62007812500005,0.18669402020384784,26.232899403824533],
];
$tol=0.00001;
foreach($expected as $c=>$v){
    $r=TemporisMetodologia2026::confronto($line,$c,50,'exponencial50',2026);
    $growth=100*$r['delta_observado']/$r['k_corte'];
    $actual=[$r['previsto'],$r['observado'],$r['erro_pct_delta'],$growth];
    foreach($v as $i=>$e)if(abs($actual[$i]-$e)>$tol){
        throw new RuntimeException("Valor divergente para $c: índice $i, previsto $e, obtido {$actual[$i]}");
    }
    printf("OK %d→%d | previsto %.3f | observado %.3f | erro %.2f%% | aumento %.2f%%\n",$c,$c+50,$actual[0],$actual[1],$actual[2],$actual[3]);
}
$stat=TemporisMetodologia2026::validacao($line,'exponencial50',50,2026);
if($stat['n']!==12 || abs($stat['wape']-31.13)>0.015)throw new RuntimeException('WAPE ou quantidade de cortes divergentes');
printf("OK 12 cortes | WAPE %.2f%%\n",$stat['wape']);
