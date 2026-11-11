-- Banco-alvo: aplicar no banco onde a migração será executada (ex.: mat_observatorio, mat_temporis_curadoria).
-- Cria tabela de controle de migrações (idempotente).
CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  version VARCHAR(32) NOT NULL,
  description VARCHAR(255) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_schema_migrations_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
