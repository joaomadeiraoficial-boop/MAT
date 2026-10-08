<?php
$app = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';

date_default_timezone_set($app['timezone']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Normalizer.php';
require_once __DIR__ . '/../src/Schema.php';
require_once __DIR__ . '/../src/ScopeClassifier.php';
require_once __DIR__ . '/../src/Temporis.php';
require_once __DIR__ . '/../src/Audit.php';
require_once __DIR__ . '/../src/ImportService.php';
require_once __DIR__ . '/../src/DuplicateDetector.php';
require_once __DIR__ . '/../src/ExternalCollector.php';

$db = new Database($dbConfig);
$pdo = $db->pdo();
Schema::ensure($pdo);
ScopeClassifier::configure($pdo);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
