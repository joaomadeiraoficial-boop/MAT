<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$page=(string)($_GET['page']??'observatorio');
$allowed=['observatorio','atlas','linha','galeria','temporis','temporis_legado','metodo','acervo','marco','editar','importar','login','laboratorio','triagem','fontes','duplicatas','auditoria','andamento','rodadas','fila_rapida'];
if(!in_array($page,$allowed,true)){$page='observatorio';http_response_code(404);}
$navPage=$page==='marco'?'acervo':$page;
$pageLabels=['observatorio'=>'O observatório','atlas'=>'Atlas das eras','linha'=>'Linha do tempo','temporis'=>'Têmporis reformulada','temporis_legado'=>'Têmporis original (legado)','metodo'=>'Caderno de método','acervo'=>'Acervo de marcos','marco'=>'Dossiê do marco','editar'=>'Editor de dossiê','importar'=>'Laboratório de importação','login'=>'Acesso do pesquisador','laboratorio'=>'Laboratório científico','triagem'=>'Triagem científica','fontes'=>'Fontes e proveniência','duplicatas'=>'Análise de duplicatas','auditoria'=>'Trilha de auditoria','andamento'=>'Progresso científico','rodadas'=>'Rodadas de pesquisa','fila_rapida'=>'Fila de revisão rápida'];
if(in_array($page, ['rodadas','fila_rapida'], true) && !is_admin())redirect_to('login',['return'=>$page]);
$stats=app_stats();$flash=take_flash();
require __DIR__.'/views/header.php';
require __DIR__.'/views/'.$page.'.php';
require __DIR__.'/views/footer.php';
