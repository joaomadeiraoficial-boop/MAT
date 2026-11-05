<?php
declare(strict_types=1);
/** Verificações sem banco: menu agrupado, contrato de leitura e higiene de URL. */
$root = dirname(__DIR__);
require_once $root . '/app/CuradoriaRodadas.php';
$failed = 0;
function check(bool $ok, string $name): void {
    global $failed;
    echo ($ok ? '[OK] ' : '[ERRO] ') . $name . PHP_EOL;
    if (!$ok) $failed++;
}
function cfg(string $k, mixed $default=null):mixed {return $k==='db_name'?'mat_observatorio':$default;}
$header = file_get_contents($root . '/views/header.php');
$index = file_get_contents($root . '/index.php');
$view = file_get_contents($root . '/views/rodadas.php');
$login = file_get_contents($root . '/views/login.php');
$actions = file_get_contents($root . '/actions.php');
$css = file_get_contents($root . '/assets/navegacao-grupos.css');
$js = file_get_contents($root . '/assets/app.js');
check(substr_count($header, 'data-nav-group="') === 3, 'Três grupos nativos independentes');
check(str_contains($header, 'O Observatório') && str_contains($header, 'Exploração histórica') && str_contains($header, 'Índice Têmporis') && str_contains($header, 'Pesquisa e curadoria'), 'Quatro áreas principais');
check(str_contains($header, "mat_url('rodadas')") && str_contains($index, "'rodadas'"), 'Nova rota de rodadas no menu e no controlador');
check(str_contains($index, "['rodadas','fila_rapida']") && str_contains($view, '!is_admin()'), 'Dossiês protegidos por autenticação');
check(str_contains($index, "['rodadas','fila_rapida']") && !str_contains($index, "['andamento', 'rodadas']") && str_contains($header, "mat_url('andamento')"), 'Resumo de progresso disponível sem login');
check(str_contains(file_get_contents($root.'/views/andamento.php'), 'progressSnapshot($curDb, is_admin())'), 'Histórico interno restrito ao pesquisador');
check(str_contains($login, 'return_page') && str_contains($actions, "['rodadas','andamento','fila_rapida']"), 'Voltar a Rodadas após entrar com conta pesquisadora');
check(str_contains($header, 'MAT_NAV_EXTENSIONS_V07_START') && str_contains($header,'resultado_temporis_piloto.php'), 'Extensões científicas antigas preservadas');
check(str_contains($header,'mat_proj/') && str_contains($header,'duplicatas') && !str_contains($header,'href="assistente_ia.php"'), 'Laboratório preservado, atalho legado não interfere na contagem');
check(str_contains($js, 'toggleNav') && str_contains($js, '.mat-nav-group') && str_contains($js, 'Escape'), 'Interação móvel e grupos expansíveis preparados');
check(str_contains($css, 'var(--serif)') && str_contains($css, 'var(--mono)'), 'Paleta e tipografia editorial do MAT reutilizadas');
check(str_contains($view, 'CuradoriaRodadas::connect') && str_contains($view, 'memória de cálculo'), 'Página apresenta fonte real e preserva pesquisa pendente');
check(CuradoriaRodadas::sourceUrl('javascript:alert(1)') === null, 'Bloqueio de URL javascript');
check(CuradoriaRodadas::sourceUrl('file:///etc/passwd') === null, 'Bloqueio de URL file');
check(CuradoriaRodadas::sourceUrl('https://example.org/reference') === 'https://example.org/reference', 'URL HTTPS válida permitida');
check(CuradoriaRodadas::decodeArray('["A", "B"]') === ['A','B'] && CuradoriaRodadas::decodeArray('{"a":1}') === [], 'JSON somente lista validado');
check(count(CuradoriaRodadas::CRITERIOS) === 11, '11 critérios oficiais preservados');
check(CuradoriaRodadas::databaseName() === 'mat_temporis_curadoria', 'Curadoria consulta apenas o banco com os 10 dossiês');
check(str_contains(file_get_contents($root.'/app/CuradoriaRodadas.php'), 'FROM cur_candidatos') && !str_contains(file_get_contents($root.'/app/CuradoriaRodadas.php'), 'import_itens'), 'Sem contagem legada ou importação de 300');
check(!str_contains($view, 'INSERT INTO') && !str_contains($view, 'DELETE FROM'), 'Tela de consulta não modifica o banco');
echo $failed ? "FALHA: $failed verificações.\n" : "NAVEGAÇÃO E CURADORIA: TESTES APROVADOS.\n";
exit($failed ? 1 : 0);
