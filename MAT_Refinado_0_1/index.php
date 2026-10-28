<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$page=(string)($_GET['page']??'observatorio');
$allowed=['observatorio','atlas','linha','galeria','temporis','metodo','acervo','marco','editar','importar','login','laboratorio','triagem','fontes','duplicatas','auditoria'];
if(!in_array($page,$allowed,true)){$page='observatorio';http_response_code(404);}
$navPage=$page==='marco'||$page==='editar'?'acervo':$page;
$pageLabels=['observatorio'=>'O observatório','atlas'=>'Atlas das eras','linha'=>'Linha do tempo','temporis'=>'Índice Têmporis','metodo'=>'Caderno de método','acervo'=>'Acervo de marcos','marco'=>'Dossiê do marco','editar'=>'Editor de dossiê','importar'=>'Laboratório de importação','login'=>'Acesso do pesquisador','laboratorio'=>'Laboratório científico','triagem'=>'Triagem científica','fontes'=>'Fontes e proveniência','duplicatas'=>'Análise de duplicatas','auditoria'=>'Trilha de auditoria'];
$stats=app_stats();$flash=take_flash();
require __DIR__.'/views/header.php';
require __DIR__.'/views/'.$page.'.php';
require __DIR__.'/views/footer.php';
