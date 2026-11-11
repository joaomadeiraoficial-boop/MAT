-- MAT: consulta de diagnóstico, somente leitura (não modifica nenhum registro).
-- Execute em phpMyAdmin, aba SQL, com mat_temporis_curadoria selecionado.
SELECT 'Candidatos registrados' AS indicador, COUNT(*) AS quantidade FROM cur_candidatos
UNION ALL
SELECT 'Pendentes de decisão humana', COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana IN ('NAO_REVISADO','EM_REVISAO')
UNION ALL
SELECT 'Aprovados preliminarmente', COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana='APROVADO'
UNION ALL
SELECT 'Homologados', COUNT(*) FROM cur_candidatos WHERE homologado=1 AND estado_revisao_humana='HOMOLOGADO'
UNION ALL
SELECT 'Notas mínimas provisórias (nova regra)', COUNT(*) FROM cur_criterios WHERE situacao='PROVISORIA_SEM_EVIDENCIA'
UNION ALL
SELECT 'Notas sem pontuação', COUNT(*) FROM cur_criterios WHERE nota IS NULL
UNION ALL
SELECT 'Notas zero restantes', COUNT(*) FROM cur_criterios WHERE nota=0
UNION ALL
SELECT 'Notas de 1 a 3', COUNT(*) FROM cur_criterios WHERE nota BETWEEN 1 AND 3;

SELECT c.id_extracao,c.titulo_proposto,c.ano_original,c.ano_validado,c.estado_revisao_humana,
       COUNT(t.codigo) AS criterios_cadastrados,
       SUM(CASE WHEN t.nota BETWEEN 1 AND 3 THEN 1 ELSE 0 END) AS notas_1_a_3,
       SUM(CASE WHEN t.situacao='PROVISORIA_SEM_EVIDENCIA' THEN 1 ELSE 0 END) AS notas_provisorias,
       (SELECT COUNT(*) FROM cur_fontes f WHERE f.id_extracao=c.id_extracao) AS fontes_cadastradas
FROM cur_candidatos c LEFT JOIN cur_criterios t ON t.id_extracao=c.id_extracao
GROUP BY c.id_extracao,c.titulo_proposto,c.ano_original,c.ano_validado,c.estado_revisao_humana
ORDER BY notas_provisorias DESC,c.id_extracao ASC LIMIT 25;
