<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TemporisGraficos.php';
require_once dirname(__DIR__).'/app/TemporisBacktest.php';
require_once dirname(__DIR__).'/app/TemporisCuradoria.php';
function checkGraphs(bool $ok,string $msg):void {if(!$ok)throw new RuntimeException('FALHOU: '.$msg);echo "[OK] $msg\n";}
$p=[
 ['id'=>'MCD-00001','title'=>'Aprovado antigo','year'=>1800,'year_inicio'=>1800,'year_fim'=>1800,'score'=>1.2,'estado_revisao_humana'=>'APROVADO','homologado'=>0],
 ['id'=>'MCD-00002','title'=>'Processo','year'=>1850,'year_inicio'=>1820,'year_fim'=>1880,'score'=>.8,'estado_revisao_humana'=>'EM_REVISAO','homologado'=>0],
 ['id'=>'MCD-00003','title'=>'Homologado posterior','year'=>2000,'year_inicio'=>2000,'year_fim'=>2000,'score'=>1.0,'estado_revisao_humana'=>'HOMOLOGADO','homologado'=>1],
];
$base=TemporisGraficos::curvas($p,1920);
checkGraphs(count($base['series'])===3 && abs($base['k_base']-2.)<1e-9,'Base só acumula marcos até o ano de referência');
checkGraphs(abs($base['series'][1]['log']-100)<1e-8 && abs($base['series'][1]['potencia']-100)<1e-8,'Ambos índices normalizam a base para 100');
checkGraphs($base['series'][2]['log']>100. && $base['series'][2]['potencia']>100.,'Após 1920, valores crescem sem depender da ordem dos registros');
checkGraphs(TemporisGraficos::indice(3.,0.)===null,'Sem K-base não há índice fictício');
$html=TemporisGraficos::svgCronologico(array_reverse($p),1920);
checkGraphs(str_contains($html,'MCD-00003') && str_contains($html,'1800') && str_contains($html,'2000'),'SVG usa IDs e anos verdadeiros no eixo temporal');
checkGraphs(str_contains($html,'intervalo 1820 a 1880') && str_contains($html,'HOMOLOGADO'),'Faixa de datação e status registrados em tooltip');
$zoom=TemporisGraficos::svgCronologico($p,1920,'log',true,1800);
checkGraphs(str_contains($zoom,'Base 1920'),'Zoom conserva o ano-base no eixo horizontal');
$tooFew=TemporisGraficos::svgCronologico([$p[0]],1920);
checkGraphs(str_contains($tooFew,'pelo menos dois marcos'),'SVG não inventa curvas com um único ponto');
$testPoints=[];
for($i=0;$i<24;$i++)$testPoints[]=['year'=>1800+$i*10,'weight'=>1.+($i%3)*.25];
$exp=TemporisBacktest::experiment($testPoints,1920,2020);
checkGraphs($exp['status']==='comparavel' && $exp['t_observed']!==null,'Backtesting produz referência histórica com amostra válida');
$svg=TemporisGraficos::svgPrevisao($exp);
checkGraphs(str_contains($svg,'Observado') && str_contains($svg,'RMSE'),'Gráfico compara previsão com observação real do conjunto');
checkGraphs(str_contains(TemporisGraficos::svgPrevisao(['models'=>[],'t_observed'=>null]),'Sem previsões comparáveis'),'Não apresenta erro inexistente como zero');
checkGraphs(str_contains(TemporisGraficos::svgPrevisao($exp,['media50'=>['n_folds'=>4,'rmse'=>3.0]]),'Faixa descritiva'),'A faixa ±RMSE é exibida só quando há ao menos 3 testes');
$view=file_get_contents(dirname(__DIR__).'/experimento_temporis.php');
checkGraphs(str_contains($view,'TemporisCuradoria::consultar') && str_contains($view,'CuradoriaRodadas::connect'),'Backtest usa banco de 800 dossiês e não confunde com legado');
checkGraphs(!str_contains($view,'simulatedPoints('),'Página não usa pontos simulados');
checkGraphs(str_contains(TemporisGraficos::svgDecisoes([['id'=>'MCD-1','title'=>'Evento','year'=>1912,'estado'=>'APROVADO','homologado'=>0,'notas_completas'=>false]]),'1912'),'Aprovações são posicionadas em eixo separado mesmo sem notas');
$humano=['id_extracao'=>'MCD-10001','titulo_proposto'=>'Marco com parecer','ano_original'=>1801,'ano_validado'=>1805,'estado_revisao_humana'=>'APROVADO','homologado'=>0,'analise_historica_json'=>'{}'];
$calcHumano=TemporisCuradoria::calcular([$humano],[],[],1920);
checkGraphs(count($calcHumano['decisoes_humanas'])===1 && $calcHumano['decisoes_humanas'][0]['year']===1805 && $calcHumano['previa']['included']===0,'Marco aprovado é plotável em 1805 sem falsificar peso ou homologação');
$view2=file_get_contents(dirname(__DIR__).'/views/temporis_legado.php');
checkGraphs(str_contains($view2,'duas transformações') && str_contains($view2,'não é o índice legado oficial'),'Distinção entre fórmula original e escala legada registrada');
echo "MAT: TESTES DE CRONOLOGIA E ERRO CONCLUÍDOS.\n";
