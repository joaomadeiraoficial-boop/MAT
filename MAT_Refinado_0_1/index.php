<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$page=(string)($_GET['page']??'observatorio');
$allowed=['observatorio','atlas','linha','temporis','temporis_final','temporis_reformulado','temporis_legado','destaques_previsoes','metodo','backtesting','acervo','marco','editar','importar','login','laboratorio','fontes','duplicatas','andamento','rodadas','fila_rapida','demonstracao_formulas'];
if(!in_array($page,$allowed,true)){$page='observatorio';http_response_code(404);}
$navPage=$page==='marco'?'acervo':$page;
$pageLabels=['observatorio'=>'O observatório','atlas'=>'Atlas das eras','linha'=>'Linha do tempo','temporis'=>'Têmporis · método atual (MAT Final)','temporis_final'=>'MAT Final · método atual','temporis_reformulado'=>'Têmporis reformulada (histórico)','temporis_legado'=>'Têmporis original (legado)','destaques_previsoes'=>'★ Melhores previsões · 50 anos','metodo'=>'Caderno de fórmulas','backtesting'=>'Backtesting · teste de previsões','demonstracao_formulas'=>'Demonstrativo simples','acervo'=>'Acervo de marcos','marco'=>'Dossiê do marco','editar'=>'Editor de dossiê','importar'=>'Laboratório de importação','login'=>'Acesso do pesquisador','laboratorio'=>'Laboratório de evidências','fontes'=>'Fontes e proveniência','duplicatas'=>'Análise de duplicatas','andamento'=>'Progresso científico','rodadas'=>'Rodadas de pesquisa','fila_rapida'=>'Fila de revisão rápida'];
if(in_array($page, ['rodadas','fila_rapida'], true) && !is_admin())redirect_to('login',['return'=>$page]);
$stats=app_stats();$flash=take_flash();
require __DIR__.'/views/header.php';
// Mantemos as URLs históricas e o motor anterior, mas o endereço normal do Têmporis agora abre a nova metodologia.
$view = match ($page) {
    'temporis' => 'temporis_final',
    'temporis_reformulado' => 'temporis',
    default => $page,
};
require __DIR__.'/views/'.$view.'.php';
require __DIR__.'/views/footer.php';
