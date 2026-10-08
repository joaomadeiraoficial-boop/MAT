<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_csrf();
try {
    $count = DuplicateDetector::rebuildAll($pdo, $app['duplicate_threshold'], $app['duplicate_year_tolerance']);
    Audit::log($pdo, 'duplicate', null, 'rebuild_candidates', null, ['pending' => $count]);
    echo json_encode(['ok' => true, 'count' => $count], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
