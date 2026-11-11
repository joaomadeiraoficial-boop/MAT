<?php
declare(strict_types=1);
/**
 * Verificação de consistência (somente leitura) para bancos configurados.
 * Uso: C:\xampp\php\php.exe cli\verificar_consistencia_db.php
 */
require_once dirname(__DIR__).'/app/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Apenas linha de comando.\n"); }

$errors = 0; $warnings = 0; $checks = 0;
function ok(string $msg): void { global $checks; $checks++; echo "[OK] $msg\n"; }
function warn(string $msg): void { global $warnings, $checks; $warnings++; $checks++; echo "[AVISO] $msg\n"; }
function fail(string $msg): void { global $errors, $checks; $errors++; $checks++; echo "[ERRO] $msg\n"; }
function scalar(PDO $pdo, string $sql, array $params = []) {
    $st = $pdo->prepare($sql); $st->execute($params); return $st->fetchColumn();
}

echo "VERIFICAÇÃO DE CONSISTÊNCIA (somente leitura)\n";
echo "Driver pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'disponível' : 'INDISPONÍVEL') . "\n\n";

// --- Observatório (mat_observatorio) ---
echo "== Observatório (db_name) ==\n";
try {
    $pdoObs = function_exists('db') ? db() : null;
    if (!$pdoObs instanceof PDO) {
        warn('Conexão indisponível (db()). Verifique config.local.php e o MySQL.');
    } else {
        ok('Conectado: ' . (string)cfg('db_name','mat_observatorio'));
        foreach (['usuarios','fontes','marcos','import_lotes','import_itens','revisoes','marco_fontes','duplicate_candidates'] as $t) {
            $exists = (int)scalar($pdoObs, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?", [$t]);
            $exists ? ok("Tabela presente: $t") : fail("Tabela ausente: $t");
        }
        // Domínios básicos em marcos
        $n = (int)scalar($pdoObs, "SELECT COUNT(*) FROM marcos WHERE impacto IS NOT NULL AND (impacto < 0 OR impacto > 10)");
        $n===0 ? ok('marcos.impacto dentro da faixa esperada (0-10 ou NULL)') : fail("marcos.impacto fora da faixa: $n registro(s)");
        $n = (int)scalar($pdoObs, "SELECT COUNT(*) FROM marcos WHERE confiabilidade IS NOT NULL AND (confiabilidade < 0 OR confiabilidade > 10)");
        $n===0 ? ok('marcos.confiabilidade dentro da faixa esperada') : fail("marcos.confiabilidade fora da faixa: $n");
        $n = (int)scalar($pdoObs, "SELECT COUNT(*) FROM marcos WHERE fator_fundacional IS NOT NULL AND (fator_fundacional < 0 OR fator_fundacional > 10)");
        $n===0 ? ok('marcos.fator_fundacional dentro da faixa esperada') : fail("marcos.fator_fundacional fora da faixa: $n");
        // Duplicatas inconsistentes (mesmo par invertido não detectável aqui, mas a=b)
        $n = (int)scalar($pdoObs, "SELECT COUNT(*) FROM duplicate_candidates WHERE marco_a = marco_b");
        $n===0 ? ok('duplicate_candidates sem auto-par') : fail("duplicate_candidates com auto-par: $n");
        // Itens de importação órfãos de lote
        $n = (int)scalar($pdoObs, "SELECT COUNT(*) FROM import_itens i LEFT JOIN import_lotes l ON l.id=i.lote_id WHERE l.id IS NULL");
        $n===0 ? ok('import_itens sem órfãos de lote') : fail("import_itens órfãos de lote: $n");
    }
} catch (Throwable $e) {
    warn('Falha ao verificar observatório: ' . $e->getMessage());
}

echo "\n== Curadoria (curadoria_db_name, tabelas cur_*) ==\n";
try {
    if (!class_exists('CuradoriaRodadas')) require_once dirname(__DIR__).'/app/CuradoriaRodadas.php';
    $pdoCur = CuradoriaRodadas::connect();
    if (!$pdoCur instanceof PDO) {
        warn('Conexão indisponível (CuradoriaRodadas::connect()). Verifique banco/permissões.');
    } else {
        ok('Conectado: ' . CuradoriaRodadas::databaseName());
        $canonical = ['impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia','conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia','fund_pervasividade','fund_aprimoramento','fund_complementaridade'];
        $ph = implode(',', array_fill(0, count($canonical), '?'));

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM cur_criterios WHERE nota IS NOT NULL AND (nota < 0 OR nota > 3)");
        $n===0 ? ok('cur_criterios.nota no domínio (NULL ou 0..3)') : fail("cur_criterios.nota fora do domínio: $n");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM cur_criterios WHERE codigo NOT IN ($ph)", $canonical);
        $n===0 ? ok('cur_criterios.codigo no conjunto canônico') : fail("cur_criterios.codigo fora do canônico: $n");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM (SELECT id_extracao FROM cur_criterios GROUP BY id_extracao, codigo HAVING COUNT(*) > 1) d");
        $n===0 ? ok('cur_criterios sem códigos duplicados por candidato') : fail("cur_criterios com duplicidade (id_extracao,codigo): $n grupo(s)");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM (SELECT id_extracao FROM cur_criterios GROUP BY id_extracao HAVING COUNT(*) <> 11) d");
        $n===0 ? ok('Todos os candidatos com 11 critérios cadastrados') : warn("Candidatos com quantidade de critérios != 11: $n (pode ser esperado em bases parciais)");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM cur_criterios k LEFT JOIN cur_candidatos c ON c.id_extracao=k.id_extracao WHERE c.id_extracao IS NULL");
        $n===0 ? ok('cur_criterios sem órfãos') : fail("cur_criterios órfãos: $n");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM cur_fontes f LEFT JOIN cur_candidatos c ON c.id_extracao=f.id_extracao WHERE c.id_extracao IS NULL");
        $n===0 ? ok('cur_fontes sem órfãos') : fail("cur_fontes órfãs: $n");

        $n = (int)scalar($pdoCur, "SELECT COUNT(*) FROM cur_candidatos WHERE ano_original = 0 OR ano_validado = 0");
        $n===0 ? ok('cur_candidatos sem ano zero') : warn("cur_candidatos com ano 0: $n (verificar regra de datação)");

        $states = $pdoCur->query("SELECT estado_revisao_humana, homologado, COUNT(*) qtd FROM cur_candidatos GROUP BY estado_revisao_humana, homologado ORDER BY qtd DESC")->fetchAll(PDO::FETCH_ASSOC);
        ok('Resumo estados/homologação: ' . json_encode($states, JSON_UNESCAPED_UNICODE));
    }
} catch (Throwable $e) {
    warn('Falha ao verificar curadoria: ' . $e->getMessage());
}

echo "\n== Concluído ==\n";
echo "Checks: $checks | Avisos: $warnings | Erros: $errors\n";
echo "Nenhum dado foi alterado.\n";
exit($errors > 0 ? 1 : 0);
