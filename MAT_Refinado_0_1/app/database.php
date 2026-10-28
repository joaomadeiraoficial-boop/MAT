<?php
declare(strict_types=1);
function db(): ?PDO {
    static $loaded = false, $pdo = null;
    if ($loaded) return $pdo;
    $loaded = true;
    try {
        $pdo = new PDO(
            'mysql:host=' . cfg('db_host') . ';port=' . (int)cfg('db_port',3306) . ';dbname=' . cfg('db_name') . ';charset=utf8mb4',
            (string)cfg('db_user'), (string)cfg('db_pass'),
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_TIMEOUT=>2]
        );
        $pdo->query('SELECT COUNT(*) FROM marcos')->fetchColumn();
    } catch (Throwable $e) { error_log('MAT: Banco indisponivel: '. $e->getMessage()); $pdo = null; }
    return $pdo;
}
