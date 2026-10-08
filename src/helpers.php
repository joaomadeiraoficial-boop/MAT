<?php
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf'] ?? '') . '">';
}

function require_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Token CSRF inválido. Recarregue a página.');
    }
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function status_label(string $status): string {
    return match ($status) {
        'aprovado' => 'Aprovado',
        'rejeitado' => 'Rejeitado',
        'duplicado' => 'Duplicado',
        'revisao' => 'Em revisão',
        default => 'Rascunho',
    };
}

function status_class(string $status): string {
    return match ($status) {
        'aprovado' => 'badge-success',
        'rejeitado' => 'badge-danger',
        'duplicado' => 'badge-warning',
        'revisao' => 'badge-info',
        default => 'badge-muted',
    };
}


function scope_nature_label(string $nature): string {
    return match ($nature) {
        'tecnologico' => 'Tecnológico',
        'descoberta_cientifica' => 'Descoberta científica',
        'metodo_infraestrutura' => 'Método / infraestrutura científica',
        'social_politico' => 'Social / político',
        'biologico_evolutivo' => 'Biológico / evolutivo',
        'cosmologico' => 'Cosmológico',
        'misto' => 'Misto',
        'outro' => 'Outro',
        default => 'Não classificado',
    };
}

function eligibility_label(string $value): string {
    return match ($value) {
        'sim' => 'Elegível',
        'nao' => 'Fora do escopo',
        default => 'Revisar escopo',
    };
}

function eligibility_class(string $value): string {
    return match ($value) {
        'sim' => 'badge-success',
        'nao' => 'badge-danger',
        default => 'badge-warning',
    };
}

function format_year_value($value): string {
    if ($value === null || $value === '') return '?';
    $f = (float)$value;
    if (abs($f - round($f)) < 0.000001) return (string)(int)round($f);
    return rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
}
