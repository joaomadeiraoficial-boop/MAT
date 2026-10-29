<?php
declare(strict_types=1);
/**
 * Reparacao conservadora do modo real MAT v1.4.
 * Detecta um defeito especifico de aspas no repositorio; faz backup e
 * valida sintaxe antes de concluir. Nao altera MySQL nem avaliacao alguma.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Somente CLI'); }
$root = dirname(__DIR__);
$repo = $root . '/app/repository.php';
$activator = $root . '/cli/ativar_modo_real.php';
$php = PHP_BINARY;
$mods = [];

if (!is_file($repo)) { fwrite(STDERR, '[ERRO] app/repository.php nao encontrado.' . PHP_EOL); exit(2); }

$fixes = [
    // A v1.0 injetava uma string SQL com aspas simples sem escape dentro de outra string PHP.
    "'SELECT * FROM marcos WHERE id = ? AND status <> 'demonstracao' LIMIT 1'" =>
        '"SELECT * FROM marcos WHERE id = ? AND status <> \'demonstracao\' LIMIT 1"',
    "'SELECT m.*, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.marco_id=m.id) AS fonte_vinculada FROM marcos m WHERE m.status <> 'demonstracao' ORDER BY ano ASC,id ASC'" =>
        '"SELECT m.*, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.marco_id=m.id) AS fonte_vinculada FROM marcos m WHERE m.status <> \'demonstracao\' ORDER BY ano ASC,id ASC"',
    // O modo real nao pode recorrer a eventos exemplificativos ao ficar sem banco.
    'if(!db()) return $status ? array_values(array_filter(sample_markers(),fn($m)=>$m[\'status\']===$status)) : sample_markers();' =>
        'if(!db()) return [];',
];
$before = file_get_contents($repo);
$after = str_replace(array_keys($fixes), array_values($fixes), $before);
if ($before !== $after) $mods[$repo] = [$before, $after];

// Garante que nao se reproduza o mesmo defeito ao executar o ativador futuramente.
if (is_file($activator)) {
    $before = file_get_contents($activator);
    $after = $before;
    $needleA = <<<'BAD'
    $s=str_replace('SELECT * FROM marcos WHERE id = ? LIMIT 1', "SELECT * FROM marcos WHERE id = ? AND status <> \'demonstracao\' LIMIT 1", $s);
BAD;
    $fixedA = <<<'GOOD'
    $s=str_replace('SELECT * FROM marcos WHERE id = ? LIMIT 1', "SELECT * FROM marcos WHERE id = ? AND status <> \\'demonstracao\\' LIMIT 1", $s);
GOOD;
    $needleB = <<<'BAD'
    $s=str_replace('FROM marcos m ORDER BY ano ASC,id ASC', "FROM marcos m WHERE m.status <> \'demonstracao\' ORDER BY ano ASC,id ASC", $s);
BAD;
    $fixedB = <<<'GOOD'
    $s=str_replace('FROM marcos m ORDER BY ano ASC,id ASC', "FROM marcos m WHERE m.status <> \\'demonstracao\\' ORDER BY ano ASC,id ASC", $s);
GOOD;
    $after = str_replace([$needleA, $needleB], [$fixedA, $fixedB], $after);
    if ($before !== $after) $mods[$activator] = [$before, $after];
}

function lint_php(string $path): array {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines=[]; $exit=0;
    exec($cmd, $lines, $exit);
    return [$exit, implode(PHP_EOL, $lines)];
}

if ($mods === []) {
    [$rc, $message] = lint_php($repo);
    echo $message . PHP_EOL;
    if ($rc !== 0) { fwrite(STDERR, '[ERRO] Repositorio tem erro diferente do conhecido. Nao houve alteracao.' . PHP_EOL); exit(1); }
    echo '[OK] Reparacao ja aplicada; nenhum arquivo alterado.' . PHP_EOL;
    exit(0);
}

$backupDir = dirname($root) . '/.mat-backups-v14';
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
    fwrite(STDERR, '[ERRO] Nao foi possivel criar pasta de backup.' . PHP_EOL); exit(3);
}
file_put_contents($backupDir . '/.htaccess', "Require all denied\nDeny from all\n");
$suffix = date('Ymd-His') . '-' . getmypid();
$backups=[];
foreach ($mods as $file=>[$old,$new]) {
    $backup=$backupDir.'/'.basename($file).'-'.$suffix.'.bak';
    if (!copy($file,$backup)) { fwrite(STDERR, '[ERRO] Falha ao criar backup; nenhuma alteracao.' . PHP_EOL); exit(3); }
    $backups[$file]=$backup;
}
$changed=[];
foreach ($mods as $file=>[$old,$new]) {
    if (file_put_contents($file,$new,LOCK_EX)===false) {
        foreach ($changed as $f) copy($backups[$f], $f);
        fwrite(STDERR,'[ERRO] Falha ao escrever. Backups preservados.' . PHP_EOL); exit(3);
    }
    $changed[]=$file;
    [$rc,$output]=lint_php($file);
    if ($rc!==0) {
        foreach ($changed as $f) copy($backups[$f],$f);
        fwrite(STDERR,'[ERRO] Sintaxe invalida. Alteracoes restauradas: '.$output.PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . basename($file) . ' corrigido; backup em '.basename($backups[$file]).PHP_EOL;
}
echo 'MAT v1.4: REPARACAO CONCLUIDA. Nenhum registro MySQL alterado.' . PHP_EOL;
