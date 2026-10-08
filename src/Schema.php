<?php
final class Schema {
    public static function ensure(PDO $pdo): void {
        self::ensureMarcoScope($pdo);
        self::ensureCollectorSchema($pdo);
        self::ensureScientificImportSchema($pdo);
        self::ensureScopeDictionary($pdo);
    }

    private static function ensureMarcoScope(PDO $pdo): void {
        $cols = $pdo->query('SHOW COLUMNS FROM marcos')->fetchAll(PDO::FETCH_COLUMN);
        $changes = [];
        if (!in_array('natureza', $cols, true)) $changes[] = "ADD COLUMN natureza VARCHAR(40) NOT NULL DEFAULT 'nao_classificado' AFTER origem";
        if (!in_array('elegibilidade_temporis', $cols, true)) $changes[] = "ADD COLUMN elegibilidade_temporis ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar' AFTER natureza";
        if (!in_array('sugestao_elegibilidade', $cols, true)) $changes[] = "ADD COLUMN sugestao_elegibilidade ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar' AFTER elegibilidade_temporis";
        if (!in_array('motivo_escopo', $cols, true)) $changes[] = "ADD COLUMN motivo_escopo TEXT NULL AFTER sugestao_elegibilidade";
        if (!in_array('escopo_revisado', $cols, true)) $changes[] = "ADD COLUMN escopo_revisado TINYINT(1) NOT NULL DEFAULT 0 AFTER motivo_escopo";
        if (!in_array('classificador_versao', $cols, true)) $changes[] = "ADD COLUMN classificador_versao VARCHAR(30) NULL AFTER escopo_revisado";
        if (!in_array('scope_score', $cols, true)) $changes[] = "ADD COLUMN scope_score INT NOT NULL DEFAULT 0 AFTER classificador_versao";
        if (!in_array('scope_confidence', $cols, true)) $changes[] = "ADD COLUMN scope_confidence VARCHAR(12) NOT NULL DEFAULT 'baixa' AFTER scope_score";
        if ($changes) $pdo->exec('ALTER TABLE marcos ' . implode(', ', $changes));
    }

    private static function ensureCollectorSchema(PDO $pdo): void {
        $batchCols = $pdo->query('SHOW COLUMNS FROM import_batches')->fetchAll(PDO::FETCH_COLUMN);
        $batchChanges = [];
        if (!in_array('source_type', $batchCols, true)) $batchChanges[] = "ADD COLUMN source_type VARCHAR(50) NOT NULL DEFAULT 'csv' AFTER filename";
        if (!in_array('source_url', $batchCols, true)) $batchChanges[] = "ADD COLUMN source_url TEXT NULL AFTER source_type";
        if (!in_array('query_json', $batchCols, true)) $batchChanges[] = "ADD COLUMN query_json LONGTEXT NULL AFTER source_url";
        if ($batchChanges) $pdo->exec('ALTER TABLE import_batches ' . implode(', ', $batchChanges));

        $rawCols = $pdo->query('SHOW COLUMNS FROM marcos_raw')->fetchAll(PDO::FETCH_COLUMN);
        $rawChanges = [];
        if (!in_array('external_source', $rawCols, true)) $rawChanges[] = "ADD COLUMN external_source VARCHAR(80) NULL AFTER status";
        if (!in_array('external_id', $rawCols, true)) $rawChanges[] = "ADD COLUMN external_id VARCHAR(120) NULL AFTER external_source";
        if (!in_array('external_url', $rawCols, true)) $rawChanges[] = "ADD COLUMN external_url TEXT NULL AFTER external_id";
        if (!in_array('content_hash', $rawCols, true)) $rawChanges[] = "ADD COLUMN content_hash CHAR(64) NULL AFTER external_url";
        if ($rawChanges) $pdo->exec('ALTER TABLE marcos_raw ' . implode(', ', $rawChanges));

        $indexes = $pdo->query('SHOW INDEX FROM marcos_raw')->fetchAll();
        $indexNames = array_column($indexes, 'Key_name');
        if (!in_array('uq_raw_external', $indexNames, true)) {
            $pdo->exec('ALTER TABLE marcos_raw ADD UNIQUE KEY uq_raw_external (external_source, external_id)');
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS collector_runs (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          source_slug VARCHAR(80) NOT NULL,
          query_json LONGTEXT NULL,
          received_rows INT NOT NULL DEFAULT 0,
          imported_rows INT NOT NULL DEFAULT 0,
          skipped_rows INT NOT NULL DEFAULT 0,
          status ENUM("processando","concluido","erro") NOT NULL DEFAULT "processando",
          message TEXT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          finished_at DATETIME NULL,
          INDEX idx_collector_source (source_slug),
          INDEX idx_collector_created (created_at)
        ) ENGINE=InnoDB');
    }
    private static function ensureScientificImportSchema(PDO $pdo): void {
        $cols = $pdo->query('SHOW COLUMNS FROM marcos')->fetchAll(PDO::FETCH_COLUMN);
        $changes = [];
        if (!in_array('inventores', $cols, true)) $changes[] = "ADD COLUMN inventores TEXT NULL AFTER origem";
        if (!in_array('organizacao', $cols, true)) $changes[] = "ADD COLUMN organizacao VARCHAR(500) NULL AFTER inventores";
        if (!in_array('ambiente', $cols, true)) $changes[] = "ADD COLUMN ambiente VARCHAR(120) NULL AFTER organizacao";
        if ($changes) $pdo->exec('ALTER TABLE marcos ' . implode(', ', $changes));

        $rawCols = $pdo->query('SHOW COLUMNS FROM marcos_raw')->fetchAll();
        foreach ($rawCols as $col) {
            if (($col['Field'] ?? '') === 'raw_source' && stripos((string)($col['Type'] ?? ''), 'text') === false) {
                $pdo->exec('ALTER TABLE marcos_raw MODIFY COLUMN raw_source TEXT NULL');
                break;
            }
        }

        $sourceCols = $pdo->query('SHOW COLUMNS FROM fontes')->fetchAll();
        foreach ($sourceCols as $col) {
            if (($col['Field'] ?? '') === 'nome' && preg_match('/varchar\((\d+)\)/i', (string)($col['Type'] ?? ''), $m) && (int)$m[1] < 500) {
                $pdo->exec('ALTER TABLE fontes MODIFY COLUMN nome VARCHAR(500) NOT NULL');
                break;
            }
        }
    }

    private static function ensureScopeDictionary(PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS scope_keyword_rules (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          pattern VARCHAR(180) NOT NULL,
          natureza VARCHAR(40) NOT NULL DEFAULT "tecnologico",
          weight TINYINT UNSIGNED NOT NULL DEFAULT 3,
          field_scope VARCHAR(20) NOT NULL DEFAULT "any",
          language VARCHAR(10) NOT NULL DEFAULT "any",
          enabled TINYINT(1) NOT NULL DEFAULT 1,
          notes VARCHAR(255) NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_scope_rule (pattern,natureza,field_scope),
          INDEX idx_scope_rule_enabled (enabled,natureza)
        ) ENGINE=InnoDB');
    }

}
