<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$page=max(1,min(100000,(int)($_GET['p']??1)));
$r=get_markers(['q'=>trim((string)($_GET['q']??'')),'categoria'=>(string)($_GET['categoria']??''),'periodo'=>(string)($_GET['periodo']??''),'status'=>(string)($_GET['status']??'')],$page,25);
echo json_encode(['source'=>site_mode(),'warning'=>'Registros não validados não constituem evidências científicas.','data'=>$r],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
