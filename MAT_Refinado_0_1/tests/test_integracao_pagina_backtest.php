<?php
declare(strict_types=1);
/** Integra a pagina REAL (v1.0+). O teste antigo exigia a simulacao removida. */
$root = dirname(__DIR__);
require_once $root . '/app/bootstrap.php';
require_once $root . '/app/TemporisBacktest.php';
function mat_test_page(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, "[FALHOU] {$name}\n"); exit(1); }
    echo "[OK] {$name}\n";
}
mat_test_page(class_exists('TemporisBacktest'), 'Motor de backtesting carregado');
mat_test_page(is_file($root . '/experimento_temporis.php'), 'Pagina de experimento presente');
mat_test_page(!str_contains(file_get_contents($root . '/experimento_temporis.php'), 'simulatedPoints('), 'Pagina sem gerador de dados ficticios');
// Simula apenas uma sessao autenticada; nao cria usuario nem escreve no banco.
$_SESSION['mat_user'] = ['id' => -999, 'nome' => 'Teste automatizado'];
$_GET = ['demo' => '1']; // parametro antigo deve ser IGNORADO pela pagina atual
ob_start();
try {
    require $root . '/experimento_temporis.php';
    $html = (string)ob_get_clean();
} catch (Throwable $e) {
    ob_end_clean();
    fwrite(STDERR, '[FALHOU] Erro ao abrir pagina: ' . $e->getMessage() . "\n");
    exit(1);
}
mat_test_page(str_contains($html, 'Backtesting da') && str_contains($html, 'Têmporis'), 'Pagina real abriu');
mat_test_page(str_contains($html, 'DADOS DO BANCO') && str_contains($html, 'REGRAS ESTRITAS'), 'Pagina exige marcos e avaliacoes reais');
mat_test_page(!str_contains($html, 'EXERCÍCIO ARTIFICIAL') && !str_contains($html, 'Demonstração fictícia'), 'Parametro demo nao reativa simulacao');
mat_test_page(str_contains($html, 'Calcular previsão e erro'), 'Formulario de previsao disponivel');
mat_test_page(!str_contains($html, 'Fatal error') && !str_contains($html, 'Failed to open stream'), 'Nenhum erro fatal durante abertura');
echo "MAT BACKTEST REAL: TESTE DE INTEGRACAO APROVADO.\n";
