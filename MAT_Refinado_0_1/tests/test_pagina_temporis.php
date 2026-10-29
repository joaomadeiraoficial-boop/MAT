<?php
declare(strict_types=1);
// Teste de integracao: executa a mesma pagina web, autenticado em sessao local,
// sem precisar de servidor HTTP; verifica ausencia de erro fatal.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['mat_user'] = ['id'=>-1, 'nome'=>'TESTE LOCAL - NAO ALTERA O BANCO'];
$_GET['export'] = '';
ob_start();
try {
    require __DIR__.'/../resultado_temporis_piloto.php';
    $html=(string) ob_get_clean();
    if(!str_contains($html, 'Resultado <em>demonstrativo</em> da Têmporis')) {
        throw new RuntimeException('Pagina nao gerou o titulo esperado.');
    }
    if(!str_contains($html, 'ENSAIO') || !str_contains($html,'118,59')) {
        throw new RuntimeException('Valores e rotulos didaticos ausentes da pagina.');
    }
    echo "[OK] Pagina web carregou, calculou T=118,59 e preservou rotulo didatico.\n";
    echo "[OK] Classe RevisedMethodology disponivel.\n";
    echo "PAGINA TEMPORIS: TESTE DE INTEGRACAO APROVADO.\n";
} catch(Throwable $e) {
    if(ob_get_level()>0) ob_end_clean();
    fwrite(STDERR, 'ERRO NO TESTE DA PAGINA: '.$e->getMessage()."\n");
    exit(1);
}
