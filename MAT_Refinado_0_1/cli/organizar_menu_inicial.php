<?php
declare(strict_types=1);
/**
 * MAT v0.7: acrescenta atalhos à navegação existente e à home.
 * Não requer conexão MySQL; não reescreve links existentes.
 * Executar na raiz do clone: php MAT_Refinado_0_1/cli/organizar_menu_inicial.php
 */
$root = dirname(__DIR__);
$header = $root . '/views/header.php';
$home = $root . '/views/observatorio.php';
$extra = $root . '/views/mat_acessos_inicio.php';
$index = $root . '/index.php';
$source = [];
foreach ([$header, $home, $index, $extra] as $p) {
    if (!is_file($p)) {fwrite(STDERR, 'Arquivo necessário não encontrado: ' . $p . PHP_EOL); exit(1);}
    $source[$p] = (string)file_get_contents($p);
}

$navToken = 'MAT_NAV_EXTENSIONS_V07_START';
$homeToken = 'mat_acessos_inicio.php';
$nextHeader = $source[$header];
$nextHome = $source[$home];
$info = [];

if (!str_contains($nextHeader, $navToken)) {
    $links = [];
    $add = static function (string $detect, string $html, bool $available = true) use (&$links, $nextHeader): void {
        if ($available && !str_contains($nextHeader, $detect)) { $links[] = $html; }
    };
    $add('mat_proj/', '   <?php if (is_dir(MAT_ROOT . "/mat_proj")): ?><a href="mat_proj/" class="nav-item"><span class="nav-glyph">↗</span> <span>MAT-Proj · Projeções</span></a><?php endif; ?>');
    $add('assistente_ia.php', '   <?php if (is_file(MAT_ROOT . "/assistente_ia.php")): ?><a href="assistente_ia.php" class="nav-item"><span class="nav-glyph">✦</span> <span>Assistente de triagem IA</span></a><?php endif; ?>');
    $add('resultado_temporis_piloto.php', '   <?php if (is_file(MAT_ROOT . "/resultado_temporis_piloto.php")): ?><a href="resultado_temporis_piloto.php" class="nav-item"><span class="nav-glyph">∑</span> <span>Têmporis · Ensaio demonstrativo</span></a><?php endif; ?>');
    $routes = [
      ['avaliacao', 'Avaliar 11 critérios', '◎'],
      ['fontes', 'Fontes e evidências', '✣'],
      ['duplicatas', 'Verificar duplicatas', '≋'],
      ['auditoria', 'Auditoria científica', '☷'],
    ];
    foreach ($routes as [$route, $label, $glyph]) {
        // Se a versão não disponibilizar essa rota, não criar link quebrado.
        $available = is_file($root . '/views/' . $route . '.php')
           && str_contains($source[$index], "'" . $route . "'");
        $add("mat_url('" . $route . "')", '   <a href="<?=mat_url(\'' . $route . '\')?>" class="nav-item"><span class="nav-glyph">' . $glyph . '</span> <span>' . $label . '</span></a>', $available);
    }
    if ($links !== []) {
        $block = " <!-- " . $navToken . " -->\n"
            . " <div class=\"rail-divider\"></div>\n"
            . " <div class=\"rail-label\">FERRAMENTAS DA PESQUISA</div>\n"
            . " <nav class=\"nav-list nav-small\" aria-label=\"Ferramentas da pesquisa\">\n"
            . " <?php if (is_admin()): ?>\n"
            . implode("\n", $links)
            . "\n <?php endif; ?>\n </nav>\n"
            . " <!-- MAT_NAV_EXTENSIONS_V07_END -->\n";
        $anchor = '<div class="rail-bottom">';
        if (!str_contains($nextHeader, $anchor)) {
            fwrite(STDERR, "Estrutura do menu nao reconhecida. Nenhum arquivo foi alterado.\n");exit(1);
        }
        $nextHeader = str_replace($anchor, $block . $anchor, $nextHeader);
        $info[] = 'Links extras preparados: ' . count($links);
    } else {
        $info[] = 'Menu lateral ja possui todos os links disponiveis.';
    }
} else {
    $info[] = 'Menu lateral v0.7 ja instalado; nao duplica links.';
}
if (!str_contains($nextHome, $homeToken)) {
    $anchor = '<section class="metric-band"';
    if (!str_contains($nextHome, $anchor)) {
        fwrite(STDERR, "Inicio com estrutura inesperada. Nenhum arquivo foi alterado.\n");exit(1);
    }
    $nextHome = str_replace($anchor, "<?php require __DIR__ . '/mat_acessos_inicio.php'; ?>\n" . $anchor, $nextHome);
    $info[] = 'Atalhos da pagina inicial preparados.';
} else {
    $info[] = 'Atalhos da pagina inicial ja instalados.';
}

$changes = [];
if ($nextHeader !== $source[$header]) $changes[$header] = $nextHeader;
if ($nextHome !== $source[$home]) $changes[$home] = $nextHome;
if ($changes === []) {echo implode("\n", $info) . "\nMAT MENU: SEM ALTERACOES NECESSARIAS.\n";exit(0);}

$backupDir = dirname($root) . '/.mat-backups-menu';
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "Nao foi possivel criar diretorio de backup.\n"); exit(1);
}
file_put_contents($backupDir . '/.htaccess', "Require all denied\nDeny from all\n");
$suffix = date('Ymd-His') . '-' . getmypid();
foreach ($changes as $p => $newContent) {
    $backup = $backupDir . '/' . basename($p) . '-' . $suffix . '.bak';
    if (!copy($p, $backup)) {fwrite(STDERR, "Erro ao fazer backup: $p\n");exit(1);}
    if (file_put_contents($p, $newContent, LOCK_EX) === false) {
        copy($backup, $p);
        fwrite(STDERR, "Erro ao gravar: $p. O backup foi restaurado.\n");exit(1);
    }
    echo 'Atualizado: ' . basename($p) . ' (backup: ' . basename($backup) . ')' . PHP_EOL;
}
echo implode("\n", $info) . "\nMAT MENU: ACESSOS ADICIONADOS COM SUCESSO.\n";
