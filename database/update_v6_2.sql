USE mat_temporis_lab;
ALTER TABLE import_batches ADD COLUMN IF NOT EXISTS source_type VARCHAR(50) NOT NULL DEFAULT 'csv' AFTER filename;
ALTER TABLE import_batches ADD COLUMN IF NOT EXISTS source_url TEXT NULL AFTER source_type;
ALTER TABLE import_batches ADD COLUMN IF NOT EXISTS query_json LONGTEXT NULL AFTER source_url;
ALTER TABLE marcos_raw ADD COLUMN IF NOT EXISTS external_source VARCHAR(80) NULL AFTER status;
ALTER TABLE marcos_raw ADD COLUMN IF NOT EXISTS external_id VARCHAR(120) NULL AFTER external_source;
ALTER TABLE marcos_raw ADD COLUMN IF NOT EXISTS external_url TEXT NULL AFTER external_id;
ALTER TABLE marcos_raw ADD COLUMN IF NOT EXISTS content_hash CHAR(64) NULL AFTER external_url;
CREATE TABLE IF NOT EXISTS collector_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_slug VARCHAR(80) NOT NULL,
  query_json LONGTEXT NULL,
  received_rows INT NOT NULL DEFAULT 0,
  imported_rows INT NOT NULL DEFAULT 0,
  skipped_rows INT NOT NULL DEFAULT 0,
  status ENUM('processando','concluido','erro') NOT NULL DEFAULT 'processando',
  message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  INDEX idx_collector_source (source_slug),
  INDEX idx_collector_created (created_at)
) ENGINE=InnoDB;
