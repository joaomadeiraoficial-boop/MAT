<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$nav = file_get_contents($root.'/views/header.php');
$home = file_get_contents($root.'/views/observatorio.php');
function verify(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, '[ERRO] '.$message.PHP_EOL); exit(1); }
    echo '[OK] '.$message.PHP_EOL;
}
verify($nav !== false && str_contains($nav, 'href="mat_proj/"'), 'Menu lateral possui acesso ao MAT-Proj');
verify($home !== false && str_contains($home, 'href="mat_proj/"'), 'Página inicial possui acesso ao MAT-Proj');
verify(is_file($root.'/mat_proj/index.php'), 'Módulo MAT-Proj está instalado');
verify(is_file($root.'/index.php'), 'Página inicial do Observatório permanece presente');
verify(is_file($root.'/app/temporis.php'), 'Motor original da Têmporis permanece presente');
echo "INTEGRACAO DE NAVEGACAO MAT-PROJ: APROVADA\n";
