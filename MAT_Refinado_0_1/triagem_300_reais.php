<?php
// Rota legada preservada para favoritos; consulta canônica no banco único.
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
if (!is_admin()) { header('Location: index.php?page=login'); exit; }
header('Location: index.php?page=rodadas', true, 302);
exit;
