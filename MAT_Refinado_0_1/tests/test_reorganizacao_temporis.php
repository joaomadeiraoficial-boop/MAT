<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TemporisFinal.php';
$assert=static function(bool $condition,string $message):void{
    if(!$condition)throw new RuntimeException('Falha: '.$message);
    echo '[OK] '.$message.PHP_EOL;
};
$src=TemporisFinal::obterDados('snapshot',2026);
$assert($src['fonte']==='snapshot','Origem estática identificada corretamente');
$assert($src['dados']['audit']['total']===800,'800 candidatos brutos');
$assert($src['dados']['audit']['rejeitados']===3,'3 rejeitados auditados');
$assert($src['dados']['audit']['sem_data']===130,'130 registros sem data');
$assert(count($src['dados']['pontos'])===667,'667 elegíveis sem inclusão fictícia');
$_GET=['fonte'=>'snapshot','coorte'=>'ampla'];$page='temporis';
ob_start();require dirname(__DIR__).'/views/temporis_final.php';$html=(string)ob_get_clean();
foreach(['Candidatos na fonte','Elegíveis para cálculo','Sem data utilizável','Origem dos dados','Mostrar mais · outras fórmulas','Mostrar mais · modelos de previsão','156,24','667','800','temporis_final_snapshot.json'] as $needle){
    $assert(str_contains($html,$needle),'Página do índice apresenta '.$needle);
}
$assert(substr_count($html,'<div class="mat-final-chart"')===2,'Curva principal e comparativo separados');
$_GET=['fonte'=>'snapshot','coorte'=>'ampla','horizonte'=>'25'];$page='backtesting';
ob_start();require dirname(__DIR__).'/views/backtesting.php';$bt=(string)ob_get_clean();
foreach(['Backtesting','WAPE','Testes válidos','Mostrar mais · comparar todos os','Mostrar mais · conferir cada previsão histórica','Exponencial','Logístico','Linear','candidatos','Previsão de crescimento','Crescimento observado'] as $needle){
    $assert(str_contains($bt,$needle),'Backtesting exibe '.$needle);
}
$assert(substr_count($bt,'<tr')>=12,'Backtesting mantém os 11 modelos na tabela expandida');
$assert(substr_count($bt,'class="tf-bt-short-row')===3,'Resumo apresenta só os 3 menores erros');
$assert(str_contains($bt,'<strong>29,3%</strong>') && str_contains($bt,'Aceleração local (experimental)'), 'Destaque correto para horizonte de 25 anos no snapshot');
$assert(str_contains($bt,'<details class="tf-expander tf-bt-more"') && substr_count($bt,'<details class="tf-expander tf-bt-more"')===3,'Detalhes científicos são recolhidos por padrão');
$assert(str_contains($bt,'<details class="tf-bt-options"'), 'Fonte e coorte ficam nas opções avançadas');
$assert(str_contains($bt,'exportação histórica (não atualiza com o MySQL)'), 'A origem estática permanece identificada');
if(!extension_loaded('pdo_mysql')){
    try{TemporisFinal::obterDados('curadoria',2026);throw new RuntimeException('Não avisou sobre origem indisponível');}
    catch(RuntimeException $e){$assert(str_contains($e->getMessage(),'Não foi possível conectar'),'Fonte forçada não é substituída silenciosamente');}
}
echo 'REORGANIZACAO TEMPORIS: verificacoes offline OK'.PHP_EOL;
