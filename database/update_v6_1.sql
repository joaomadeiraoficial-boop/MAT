USE mat_temporis_lab;

ALTER TABLE marcos
  ADD COLUMN IF NOT EXISTS natureza VARCHAR(40) NOT NULL DEFAULT 'nao_classificado' AFTER origem,
  ADD COLUMN IF NOT EXISTS elegibilidade_temporis ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar' AFTER natureza,
  ADD COLUMN IF NOT EXISTS sugestao_elegibilidade ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar' AFTER elegibilidade_temporis,
  ADD COLUMN IF NOT EXISTS motivo_escopo TEXT NULL AFTER sugestao_elegibilidade,
  ADD COLUMN IF NOT EXISTS escopo_revisado TINYINT(1) NOT NULL DEFAULT 0 AFTER motivo_escopo,
  ADD COLUMN IF NOT EXISTS classificador_versao VARCHAR(30) NULL AFTER escopo_revisado;
