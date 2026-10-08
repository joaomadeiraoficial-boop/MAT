CREATE DATABASE IF NOT EXISTS mat_temporis_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mat_temporis_lab;

CREATE TABLE IF NOT EXISTS import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  source_type VARCHAR(50) NOT NULL DEFAULT 'csv',
  source_url TEXT NULL,
  query_json LONGTEXT NULL,
  delimiter_char VARCHAR(5) DEFAULT ';',
  total_rows INT NOT NULL DEFAULT 0,
  imported_rows INT NOT NULL DEFAULT 0,
  duplicate_candidates INT NOT NULL DEFAULT 0,
  status ENUM('processando','concluido','erro') NOT NULL DEFAULT 'processando',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marcos_raw (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NULL,
  row_number INT NULL,
  raw_name TEXT NULL,
  raw_year VARCHAR(100) NULL,
  raw_category VARCHAR(255) NULL,
  raw_source TEXT NULL,
  raw_location VARCHAR(255) NULL,
  raw_description TEXT NULL,
  raw_payload LONGTEXT NULL,
  status VARCHAR(40) DEFAULT 'importado',
  external_source VARCHAR(80) NULL,
  external_id VARCHAR(120) NULL,
  external_url TEXT NULL,
  content_hash CHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_raw_batch FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE SET NULL,
  INDEX idx_raw_batch (batch_id),
  UNIQUE KEY uq_raw_external (external_source, external_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marcos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  raw_id BIGINT UNSIGNED NULL,
  nome VARCHAR(500) NOT NULL,
  nome_normalizado VARCHAR(500) NOT NULL,
  ano_inicio DECIMAL(10,2) NULL,
  ano_fim DECIMAL(10,2) NULL,
  ano_ref DECIMAL(10,2) NULL,
  precisao_data ENUM('ano','intervalo','texto','desconhecida') DEFAULT 'desconhecida',
  categoria VARCHAR(150) NOT NULL DEFAULT 'Não classificado',
  subcategoria VARCHAR(150) NULL,
  civilizacao VARCHAR(150) NULL,
  pais VARCHAR(150) NULL,
  cidade VARCHAR(150) NULL,
  descricao TEXT NULL,
  origem VARCHAR(255) NULL,
  inventores TEXT NULL,
  organizacao VARCHAR(500) NULL,
  ambiente VARCHAR(120) NULL,
  natureza VARCHAR(40) NOT NULL DEFAULT 'nao_classificado',
  elegibilidade_temporis ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
  sugestao_elegibilidade ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
  motivo_escopo TEXT NULL,
  escopo_revisado TINYINT(1) NOT NULL DEFAULT 0,
  classificador_versao VARCHAR(30) NULL,
  scope_score INT NOT NULL DEFAULT 0,
  scope_confidence VARCHAR(12) NOT NULL DEFAULT 'baixa',
  impacto DECIMAL(4,2) NULL,
  confiabilidade DECIMAL(4,2) NULL,
  fator_fundacional DECIMAL(4,2) NULL,
  score_pi DECIMAL(12,6) NULL,
  justificativa_impacto TEXT NULL,
  justificativa_confiabilidade TEXT NULL,
  justificativa_fundacional TEXT NULL,
  status ENUM('rascunho','revisao','aprovado','rejeitado','duplicado') NOT NULL DEFAULT 'rascunho',
  incluir_temporis TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_marco_raw FOREIGN KEY (raw_id) REFERENCES marcos_raw(id) ON DELETE SET NULL,
  INDEX idx_marco_status (status),
  INDEX idx_marco_ano (ano_ref),
  INDEX idx_marco_categoria (categoria),
  INDEX idx_marco_normalizado (nome_normalizado(191))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS scope_keyword_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pattern VARCHAR(180) NOT NULL,
  natureza VARCHAR(40) NOT NULL DEFAULT 'tecnologico',
  weight TINYINT UNSIGNED NOT NULL DEFAULT 3,
  field_scope VARCHAR(20) NOT NULL DEFAULT 'any',
  language VARCHAR(10) NOT NULL DEFAULT 'any',
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_scope_rule (pattern,natureza,field_scope),
  INDEX idx_scope_rule_enabled (enabled,natureza)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fontes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(500) NOT NULL UNIQUE,
  tipo ENUM('artigo','livro','base_dados','site_institucional','importada','outra') NOT NULL DEFAULT 'outra',
  autores VARCHAR(500) NULL,
  ano_publicacao INT NULL,
  doi VARCHAR(255) NULL,
  url TEXT NULL,
  observacao TEXT NULL,
  verificada TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marco_fontes (
  marco_id BIGINT UNSIGNED NOT NULL,
  fonte_id BIGINT UNSIGNED NOT NULL,
  papel ENUM('origem','confirmacao','data','impacto','contexto') NOT NULL DEFAULT 'confirmacao',
  trecho TEXT NULL,
  pagina VARCHAR(50) NULL,
  PRIMARY KEY (marco_id, fonte_id, papel),
  CONSTRAINT fk_mf_marco FOREIGN KEY (marco_id) REFERENCES marcos(id) ON DELETE CASCADE,
  CONSTRAINT fk_mf_fonte FOREIGN KEY (fonte_id) REFERENCES fontes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS duplicate_candidates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marco_a BIGINT UNSIGNED NOT NULL,
  marco_b BIGINT UNSIGNED NOT NULL,
  similarity DECIMAL(5,4) NOT NULL,
  status ENUM('pendente','mesclado','nao_duplicado','ignorado') NOT NULL DEFAULT 'pendente',
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pair (marco_a, marco_b),
  CONSTRAINT fk_dup_a FOREIGN KEY (marco_a) REFERENCES marcos(id) ON DELETE CASCADE,
  CONSTRAINT fk_dup_b FOREIGN KEY (marco_b) REFERENCES marcos(id) ON DELETE CASCADE,
  INDEX idx_dup_status (status),
  INDEX idx_dup_similarity (similarity)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS revisoes_marco (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marco_id BIGINT UNSIGNED NOT NULL,
  campo VARCHAR(100) NOT NULL,
  valor_anterior TEXT NULL,
  valor_novo TEXT NULL,
  justificativa TEXT NULL,
  revisor VARCHAR(120) DEFAULT 'local',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rev_marco FOREIGN KEY (marco_id) REFERENCES marcos(id) ON DELETE CASCADE,
  INDEX idx_rev_marco (marco_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS temporis_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  alpha DECIMAL(8,5) NOT NULL DEFAULT 0.65,
  base_year INT NOT NULL DEFAULT 2026,
  base_value DECIMAL(10,4) NOT NULL DEFAULT 100,
  filtros_json LONGTEXT NULL,
  resultado_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT NULL,
  action VARCHAR(120) NOT NULL,
  before_json LONGTEXT NULL,
  after_json LONGTEXT NULL,
  actor VARCHAR(120) NULL,
  ip_address VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;


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

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('temporis_alpha','0.65'),
('temporis_base_year','2026'),
('temporis_base_value','100'),
('impact_scale_max','10'),
('confidence_scale_max','10'),
('foundational_min','1.0'),
('foundational_max','1.5')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
