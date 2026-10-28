<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}

const MAT_ROOT = __DIR__ . '/..';
$configFile = MAT_ROOT . '/config.local.php';
$config = is_file($configFile) ? require $configFile : require MAT_ROOT . '/config.example.php';
if (!is_array($config)) { throw new RuntimeException('Configuracao invalida.'); }

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/temporis.php';
foreach (['Normalizer','ScopeClassifier','DuplicateDetector','Methodology','MetricSuggester'] as $component) require_once __DIR__ . '/' . $component . '.php';
require_once __DIR__ . '/science.php';

function cfg(string $key, mixed $default = null): mixed { global $config; return $config[$key] ?? $default; }
function h(?string $str): string { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function mat_url(string $page = 'observatorio', array $params = []): string { return 'index.php?' . http_build_query(['page'=>$page] + $params); }
function redirect_to(string $page, array $params = []): never { header('Location: ' . mat_url($page,$params)); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void { if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Formulario expirado. Volte e tente novamente.'); } }
function current_user(): ?array { return $_SESSION['mat_user'] ?? null; }
function is_admin(): bool { return current_user() !== null; }
function require_admin(): void { if (!is_admin()) redirect_to('login', ['return'=>'acervo']); }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type,'message'=>$message]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function yfmt(int $year): string { return $year < 0 ? number_format(abs($year),0,',','.') . ' a.C.' : $year . ' d.C.'; }
function short_num(float|int $n): string { return number_format((float)$n, 0, ',', '.'); }
function decimal(float $n, int $digits=2): string { return number_format($n,$digits,',','.'); }
function period_from_year(int $year): string {
    if($year < -3500) return 'Pré-história';
    if($year < 476) return 'Antiguidade';
    if($year < 1453) return 'Idade Média';
    if($year < 1789) return 'Idade Moderna';
    if($year < 1945) return 'Era Industrial';
    if($year < 2000) return 'Era Digital';
    return 'Era Contemporânea';
}
function cat_tone(string $category): string {
    return match($category) {
        'Comunicação'=>'tone-amber', 'Computação'=>'tone-indigo', 'Energia'=>'tone-rust',
        'Medicina'=>'tone-green', 'Engenharia'=>'tone-blue', 'Ciência'=>'tone-cyan',
        default=>'tone-muted'
    };
}
function status_text(string $status): string { return match($status) { 'aprovado'=>'Validado', 'pendente'=>'Em revisão', 'importado'=>'Importado', 'demonstracao'=>'Exemplo', 'rejeitado'=>'Excluído', default=>$status }; }
function site_mode(): string { return db() === null ? 'demonstracao' : 'mysql'; }
