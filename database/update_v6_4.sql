-- MAT v6.4.0-alpha — classificador de escopo v2
ALTER TABLE marcos ADD COLUMN IF NOT EXISTS scope_score INT NOT NULL DEFAULT 0 AFTER classificador_versao;
ALTER TABLE marcos ADD COLUMN IF NOT EXISTS scope_confidence VARCHAR(12) NOT NULL DEFAULT 'baixa' AFTER scope_score;
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
