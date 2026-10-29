<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(403); exit('Somente terminal');}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TemporisBacktest.php';
$options=getopt('', ['corte::','alvo::','demo','json']);
$cut=isset($options['corte'])?(int)$options['corte']:1920;
$target=isset($options['alvo'])?(int)$options['alvo']:2020;
if(array_key_exists('demo',$options)){
    fwrite(STDERR,"O modo demonstrativo foi desativado. Use apenas dados reais aprovados.\n");
    exit(2);
}
try {
    if (!db()) throw new RuntimeException('MySQL indisponivel. Sem substituicao por dados ficticios.');
    if (!function_exists('revised_all_evaluations')) require_once dirname(__DIR__).'/app/revised_repository.php';
    $extracted=TemporisBacktest::approvedPoints(all_markers(),revised_all_evaluations(),$target);
    $points=$extracted['points'];$stats=$extracted+['mode'=>'BANCO_APROVADO'];
    $report=TemporisBacktest::experiment($points,$cut,$target);
    $folds=TemporisBacktest::historicalBacktests($points,$cut,$target-$cut,20);
    $out=['status_cientifico'=>'APENAS_MARCOS_APROVADOS_E_RUBRICAS_CONFIRMADAS',
        'metadata'=>$stats,'test_1920_2020'=>$report,'folds_anterior_ao_corte'=>array_map(static fn($r)=>['origin'=>$r['origin'],'target'=>$r['target'],'models'=>$r['models']],$folds),
        'summary_prior_folds'=>TemporisBacktest::summarize($folds)];
    echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
} catch(Throwable $e) {
    fwrite(STDERR,'MAT BACKTEST: '.$e->getMessage()."\n");exit(1);
}
