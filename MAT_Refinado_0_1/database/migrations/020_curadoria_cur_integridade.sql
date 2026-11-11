-- Banco-alvo: mat_temporis_curadoria
-- Objetivo: reforçar integridade de domínio para curadoria cur_*.
-- ATENÇÃO: valide a versão do MySQL/MariaDB (suporte a CHECK). Em versões sem enforcement, use os scripts de verificação.
-- Execute após backup.

-- Exemplos de constraints (ajuste nomes se já existirem):
-- ALTER TABLE cur_criterios
--   ADD CONSTRAINT chk_cur_criterios_nota CHECK (nota IS NULL OR (nota >= 0 AND nota <= 3));
--
-- Observação: estados e códigos possuem conjuntos controlados pela aplicação; constraints ENUM/CHECK adicionais
-- devem ser introduzidas apenas após confirmar todos os valores presentes na base.
--
-- Recomenda-se rodar database/verificacao_consistencia_curadoria.sql antes e depois.

-- Registro sugerido:
-- INSERT INTO schema_migrations (version, description) VALUES ('020', 'curadoria cur_* integridade (checks/verificacao)');
