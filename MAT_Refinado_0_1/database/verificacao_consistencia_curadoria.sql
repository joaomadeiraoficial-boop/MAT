-- Verificação de consistência (SOMENTE LEITURA) — banco: mat_temporis_curadoria
-- Execute no phpMyAdmin/cliente SQL com o banco correto selecionado.

-- 1) Contagens gerais
SELECT 'Candidatos' AS indicador, COUNT(*) AS valor FROM cur_candidatos
UNION ALL SELECT 'Critérios', COUNT(*) FROM cur_criterios
UNION ALL SELECT 'Fontes', COUNT(*) FROM cur_fontes
UNION ALL SELECT 'Rodadas', COUNT(*) FROM cur_rodadas
UNION ALL SELECT 'Logs', COUNT(*) FROM cur_log;

-- 2) Notas fora do domínio (esperado: NULL ou 0..3)
SELECT id_extracao, codigo, nota FROM cur_criterios
WHERE nota IS NOT NULL AND (nota < 0 OR nota > 3);

-- 3) Códigos de critério fora do conjunto canônico (11 rubricas)
SELECT c.id_extracao, c.codigo
FROM cur_criterios c
WHERE c.codigo NOT IN (
  'impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia',
  'conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia',
  'fund_pervasividade','fund_aprimoramento','fund_complementaridade'
);

-- 4) Candidatos com quantidade de critérios diferente de 11
SELECT id_extracao, COUNT(*) AS qtd_criterios
FROM cur_criterios
GROUP BY id_extracao
HAVING COUNT(*) <> 11;

-- 5) Candidatos com códigos de critério duplicados
SELECT id_extracao, codigo, COUNT(*) AS duplicados
FROM cur_criterios
GROUP BY id_extracao, codigo
HAVING COUNT(*) > 1;

-- 6) Critérios órfãos (sem candidato)
SELECT k.id_extracao, k.codigo
FROM cur_criterios k
LEFT JOIN cur_candidatos c ON c.id_extracao = k.id_extracao
WHERE c.id_extracao IS NULL;

-- 7) Fontes órfãs (sem candidato)
SELECT f.id_extracao, f.fonte_id
FROM cur_fontes f
LEFT JOIN cur_candidatos c ON c.id_extracao = f.id_extracao
WHERE c.id_extracao IS NULL;

-- 8) Estados e homologação (conferência rápida)
SELECT estado_revisao_humana, homologado, COUNT(*) AS qtd
FROM cur_candidatos
GROUP BY estado_revisao_humana, homologado
ORDER BY qtd DESC;

-- 9) Datas suspeitas (ano zero ou fora de faixa plausível configurável no domínio)
SELECT id_extracao, ano_original, ano_validado
FROM cur_candidatos
WHERE ano_original = 0 OR ano_validado = 0;
