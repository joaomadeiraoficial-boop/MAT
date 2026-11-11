-- Banco-alvo: mat_observatorio
-- Objetivo: documentar pontos de integridade para o acervo principal.
-- Execute após backup.

-- Verificações recomendadas (schema.sql já define domínios ENUM e algumas FKs/UNIQUE):
-- 1) marcos: status, elegibilidade_temporis, precisao_data dentro dos valores esperados; notas/flags consistentes.
-- 2) marcos sem fonte vinculada quando aprovados para Têmporis (conforme regra de negócio).
-- 3) import_itens/marcos órfãos e duplicate_candidates simétricos/duplicados.
--
-- Índices adicionais podem ser criados conforme consultas reais (ex.: por status/elegibilidade/ano) usando CREATE INDEX
-- compatível com a versão do servidor.

-- Registro sugerido:
-- INSERT INTO schema_migrations (version, description) VALUES ('030', 'observatorio integridade/verificacao');
