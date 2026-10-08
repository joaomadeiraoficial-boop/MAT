<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_csrf();

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($input['id'] ?? 0);
$action = (string)($input['action'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM marcos WHERE id=?');
$stmt->execute([$id]);
$before = $stmt->fetch();

if (!$before) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Marco não encontrado.']);
    exit;
}

try {
    if ($action === 'approve') {
        $pdo->prepare("UPDATE marcos SET elegibilidade_temporis='sim', escopo_revisado=1 WHERE id=?")->execute([$id]);
        $after = $before;
        $after['elegibilidade_temporis'] = 'sim';
        $after['escopo_revisado'] = 1;
        Audit::log($pdo, 'marco', $id, 'scope_approve', $before, $after);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE marcos SET elegibilidade_temporis='nao', escopo_revisado=1, incluir_temporis=0 WHERE id=?")->execute([$id]);
        $after = $before;
        $after['elegibilidade_temporis'] = 'nao';
        $after['escopo_revisado'] = 1;
        $after['incluir_temporis'] = 0;
        Audit::log($pdo, 'marco', $id, 'scope_reject', $before, $after);
    } else {
        throw new RuntimeException('Ação de escopo inválida.');
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
