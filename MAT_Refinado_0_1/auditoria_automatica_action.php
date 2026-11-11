<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Metodo nao permitido.');}
verify_csrf();
require_admin();
require_once __DIR__.'/app/CuradoriaAuditoriaAutomatica.php';
if((string)($_POST['action']??'')!=='curadoria_auditoria'){
    http_response_code(400);exit('Acao invalida.');
}
try {
    $pdo=CuradoriaRodadas::connect();
    if(!$pdo)throw new RuntimeException('Banco de curadoria indisponivel.');
    $resultado=CuradoriaAuditoriaAutomatica::registrar($pdo,$_POST,current_user()??[]);
    flash('success',$resultado);
} catch(Throwable $e) {
    error_log('MAT auditoria automatizada: '.$e->getMessage());
    flash('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'Falha na operação de auditoria. Nenhuma aprovação parcial foi salva.');
}
header('Location: auditoria_automatica.php',true,303);
exit;
