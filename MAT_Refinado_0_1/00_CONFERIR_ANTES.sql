-- SOMENTE LEITURA, pode executar antes ou depois.
-- Selecione no phpMyAdmin o banco mat_temporis_curadoria.
SELECT 'CANDIDATOS' AS indicador, COUNT(*) AS valor FROM cur_candidatos
UNION ALL SELECT 'NOTAS_PREENCHIDAS', COUNT(*) FROM cur_criterios WHERE nota IS NOT NULL
UNION ALL SELECT 'CRITERIOS_SEM_NOTA', COUNT(*) FROM cur_criterios WHERE nota IS NULL
UNION ALL SELECT 'FONTES', COUNT(*) FROM cur_fontes
UNION ALL SELECT 'APROVADOS', COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana='APROVADO'
UNION ALL SELECT 'HOMOLOGADOS', COUNT(*) FROM cur_candidatos WHERE homologado=1;
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME IN ('cur_triagem_assistida','cur_triagem_assistida_log');
