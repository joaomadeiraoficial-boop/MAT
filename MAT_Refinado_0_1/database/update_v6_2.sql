-- Atualizacao explicita v6.1 -> v6.2.
-- Execute UMA VEZ apos backup do mat_observatorio.
-- Nao executa sobre mat_temporis_lab / mat_v5.
USE mat_observatorio;
ALTER TABLE marcos
 ADD COLUMN natureza VARCHAR(40) NOT NULL DEFAULT 'nao_classificado',
 ADD COLUMN sugestao_elegibilidade ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
 ADD COLUMN elegibilidade_temporis ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
 ADD COLUMN motivo_escopo TEXT DEFAULT NULL,
 ADD COLUMN escopo_revisado TINYINT(1) NOT NULL DEFAULT 0,
 ADD COLUMN classificador_versao VARCHAR(40) DEFAULT NULL,
 ADD COLUMN scope_score INT NOT NULL DEFAULT 0,
 ADD COLUMN scope_confidence VARCHAR(12) NOT NULL DEFAULT 'baixa',
 ADD COLUMN incluir_temporis TINYINT(1) NOT NULL DEFAULT 0;
-- Complementos do laboratorio v6: as fontes sao vinculadas de forma rastreavel.
CREATE TABLE IF NOT EXISTS marco_fontes (
  marco_id INT UNSIGNED NOT NULL,
  fonte_id INT UNSIGNED NOT NULL,
  papel ENUM('origem','confirmacao','data','impacto','fundacional','confiabilidade','contexto') NOT NULL DEFAULT 'confirmacao',
  trecho TEXT DEFAULT NULL,
  pagina VARCHAR(70) DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (marco_id, fonte_id, papel),
  CONSTRAINT fk_mf_obs_marco FOREIGN KEY (marco_id) REFERENCES marcos(id) ON DELETE CASCADE,
  CONSTRAINT fk_mf_obs_fonte FOREIGN KEY (fonte_id) REFERENCES fontes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS duplicate_candidates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 marco_a INT UNSIGNED NOT NULL,
 marco_b INT UNSIGNED NOT NULL,
 similarity DECIMAL(5,4) NOT NULL,
 status ENUM('pendente','nao_duplicado','revisao_manual') NOT NULL DEFAULT 'pendente',
 revisado_em DATETIME DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_obs_pair (marco_a,marco_b),
 INDEX idx_obs_dup_status (status),
 CONSTRAINT fk_dup_obs_a FOREIGN KEY (marco_a) REFERENCES marcos(id) ON DELETE CASCADE,
 CONSTRAINT fk_dup_obs_b FOREIGN KEY (marco_b) REFERENCES marcos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
