<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
// Rota nova e isolada: nao exige modificar index.php nem o painel antigo.
if(!is_admin())redirect_to('login',['return'=>'andamento']);
$page='auditoria_automatica';$navPage=$page;
$pageLabels=[$page=>'Auditoria automatizada'];
$stats=app_stats();$flash=take_flash();
require __DIR__.'/views/header.php';
require __DIR__.'/views/auditoria_automatica.php';
require __DIR__.'/views/footer.php';
