<?php
require __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_csrf();
$id = (int)($_POST['id'] ?? 0);
$returnTo = (string)($_POST['return_to'] ?? 'importar.php');
if (!in_array($returnTo, ['importar.php','coletor.php'], true)) $returnTo = 'importar.php';
if ($id <= 0) { flash('error','Lote inválido.'); redirect('../' . $returnTo); }

$stmt = $pdo->prepare('SELECT * FROM import_batches WHERE id=?');
$stmt->execute([$id]);
$batch = $stmt->fetch();
if (!$batch) { flash('error','Lote não encontrado.'); redirect('../' . $returnTo); }

$pdo->beginTransaction();
try {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE r.batch_id=?');
    $countStmt->execute([$id]);
    $count = (int)$countStmt->fetchColumn();

    Audit::log($pdo,'import_batch',$id,'delete_batch',$batch,['deleted_marcos'=>$count]);
    $pdo->prepare('DELETE m FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE r.batch_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM marcos_raw WHERE batch_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM import_batches WHERE id=?')->execute([$id]);
    $pdo->commit();
    flash('success',"Lote #{$id} removido com {$count} marco(s). Decisões e vínculos associados foram limpos por integridade referencial.");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error','Não foi possível excluir o lote: '.$e->getMessage());
}
redirect('../' . $returnTo);
