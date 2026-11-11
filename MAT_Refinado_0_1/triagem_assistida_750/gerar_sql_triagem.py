#!/usr/bin/env python3
"""Gera tabela auxiliar de triagem, SEM updates dos registros científicos originais."""
import importlib.util, json, pathlib
P=pathlib.Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('auditar_dump',P/'auditar_dump.py')
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)

def esc(s):
    if s is None:return 'NULL'
    if isinstance(s,int):return str(s)
    return "'"+str(s).replace('\\','\\\\').replace("'","\\'").replace('\r','\\r').replace('\n','\\n').replace('\x00','')+"'"

header='''-- MAT TÊMPORIS - TRIAGEM ASSISTIDA V1 (gerada de snapshot externo)
-- IMPORTANTE: executa CREATE/INSERT IGNORE SOMENTE em NOVAS tabelas auxiliares.
-- NAO aplica UPDATE/DELETE/REPLACE em cur_candidatos, cur_criterios, cur_fontes,
-- cur_log ou cur_rodadas. Nao implica aprovacao nem homologacao.
-- Faça backup antes. Use o banco mat_temporis_curadoria, nunca mat_observatorio.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS `cur_triagem_assistida` (
  `id_extracao` varchar(12) NOT NULL,
  `titulo_snapshot` text NOT NULL,
  `hash_snapshot` char(64) NOT NULL,
  `trilha` varchar(48) NOT NULL,
  `acao_sugerida` text NOT NULL,
  `ano_validado_snapshot` int DEFAULT NULL,
  `notas_snapshot` tinyint unsigned NOT NULL,
  `pendencias_snapshot` smallint unsigned NOT NULL,
  `duplicatas_snapshot` smallint unsigned NOT NULL,
  `fontes_externas_snapshot` smallint unsigned NOT NULL,
  `situacao_triagem` enum('PENDENTE','PESQUISAR_FONTES','REVER_DATA','REVER_ESCOPO','REVER_DUPLICATA','PARECER_REGISTRADO') NOT NULL DEFAULT 'PENDENTE',
  `parecer_pesquisador` text DEFAULT NULL,
  `versao` int unsigned NOT NULL DEFAULT 0,
  `modificado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_extracao`),
  KEY `idx_triagem_trilha` (`trilha`),
  KEY `idx_triagem_situacao` (`situacao_triagem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cur_triagem_assistida_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_extracao` varchar(12) NOT NULL,
  `status_anterior` varchar(35) NOT NULL,
  `status_novo` varchar(35) NOT NULL,
  `parecer` text NOT NULL,
  `realizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cur_triagem_assistida_log_id` (`id_extracao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IMPORTANTE: no reprocessamento os pareceres humanos existentes nao sao sobrescritos.
'''
cols=['id_extracao','titulo_snapshot','hash_snapshot','trilha','acao_sugerida','ano_validado_snapshot','notas_snapshot','pendencias_snapshot','duplicatas_snapshot','fontes_externas_snapshot']
lines=[header]
for i in range(0,len(m.rows),100):
    lines.append('INSERT IGNORE INTO `cur_triagem_assistida` ('+','.join('`'+x+'`' for x in cols)+') VALUES')
    items=[]
    for r in m.rows[i:i+100]:
        vals=[r['ID'],r['Titulo'],m.SNAPSHOT_SHA256,r['Trilha'],r['Proxima_acao'],r['Ano_validado'],r['Notas_preenchidas'],r['Pendencias'],r['Duplicatas'],r['Fontes_externas']]
        items.append('('+','.join(esc(v) for v in vals)+')')
    lines.append(',\n'.join(items)+';\n')
lines.append('''-- Verificacao de carga; esperado 750 apenas se os 750 IDs ainda existem.
SELECT COUNT(*) AS linhas_auxiliares FROM cur_triagem_assistida;
SELECT trilha, COUNT(*) AS total FROM cur_triagem_assistida GROUP BY trilha ORDER BY total DESC;
-- Divergência de título = seu banco mudou após a extração; conferir antes de usar pareceres antigos.
SELECT t.id_extracao,t.titulo_snapshot,c.titulo_proposto
  FROM cur_triagem_assistida t JOIN cur_candidatos c USING(id_extracao)
 WHERE t.titulo_snapshot<>c.titulo_proposto;
''')
(P/'INSTALAR_TRIAGEM_ASSISTIDA.sql').write_text('\n'.join(lines),encoding='utf-8')
print('SQL criado, 750 registros auxiliares, bytes', (P/'INSTALAR_TRIAGEM_ASSISTIDA.sql').stat().st_size)
