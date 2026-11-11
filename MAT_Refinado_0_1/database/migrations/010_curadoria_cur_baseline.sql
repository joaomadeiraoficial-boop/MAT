-- Banco-alvo: mat_temporis_curadoria (curadoria científica, tabelas cur_*)
-- Objetivo: documentar baseline e garantir índices úteis de consulta (idempotente quando suportado).
-- Pré-requisito: esquema base criado (ex.: database/curadoria_cientifica_schema_novo.sql).

-- Índices auxiliares (nomes fixos para evitar duplicação manual; se já existirem, ajuste/remova conforme o servidor).
-- MariaDB/MySQL: CREATE INDEX IF NOT EXISTS pode não estar disponível em versões antigas; execute manualmente se necessário.
-- CREATE INDEX IF NOT EXISTS idx_cur_candidatos_estado ON cur_candidatos (estado_revisao_humana, homologado);
-- CREATE INDEX IF NOT EXISTS idx_cur_candidatos_rodada_estado ON cur_candidatos (rodada, estado_revisao_humana);
-- CREATE INDEX IF NOT EXISTS idx_cur_criterios_situacao ON cur_criterios (situacao, nota);
-- CREATE INDEX IF NOT EXISTS idx_cur_fontes_candidato ON cur_fontes (id_extracao);

-- Verificação rápida (somente leitura) após aplicar baseline:
-- SELECT COUNT(*) AS candidatos FROM cur_candidatos;
-- SELECT COUNT(*) AS criterios FROM cur_criterios;
-- SELECT COUNT(*) AS fontes FROM cur_fontes;

-- Registro sugerido:
-- INSERT INTO schema_migrations (version, description) VALUES ('010', 'curadoria cur_* baseline/index hints');
