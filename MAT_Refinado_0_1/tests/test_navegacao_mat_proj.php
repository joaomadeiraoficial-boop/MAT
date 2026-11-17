<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$nav = file_get_contents($root.'/views/header.php');
$home = file_get_contents($root.'/views/observatorio.php');
function verify(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, '[ERRO] '.$message.PHP_EOL); exit(1); }
    echo '[OK] '.$message.PHP_EOL;
}
verify($nav !== false && !str_contains($nav, "mat_url('projecoes')"), 'Menu lateral não expõe mais a página bruta removida');
verify($home !== false && !str_contains($home, "mat_url('projecoes')") && str_contains($home, "mat_url('backtesting')"), 'Página inicial aponta para o teste de previsões');
verify(is_file($root.'/mat_proj/index.php'), 'Endereço histórico do MAT-Proj permanece redirecionando');
$redirect = file_get_contents($root.'/mat_proj/index.php');
verify(str_contains($redirect, "'backtesting'"), 'Endereço antigo do MAT-Proj leva ao Backtesting');
verify(!is_file($root.'/views/projecoes.php'), 'Página bruta removida do sistema de arquivos');
verify(is_file($root.'/views/backtesting.php'), 'Backtesting permanece presente como página de projeções');
verify(is_file($root.'/index.php'), 'Página inicial do Observatório permanece presente');
verify(is_file($root.'/app/temporis.php'), 'Motor original da Têmporis permanece presente');
echo "INTEGRACAO DE NAVEGACAO MAT-PROJ: APROVADA\n";
