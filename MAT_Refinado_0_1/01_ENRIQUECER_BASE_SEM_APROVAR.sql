-- MAT / Indice Temporis: ENRIQUECIMENTO DOCUMENTAL CONSERVADOR
-- Compatibilidade: MariaDB 10.4 / MySQL 5.7+; UTF8MB4.
-- APENAS cur_criterios, cur_fontes e cur_log. Nao altera ano, nota, status,
-- pendencias, duplicatas, aprovacao, homologacao ou numero de candidatos.
-- Idempotente: marcadores [MAT-ROTEIRO-20261105] e [MAT-CAP-20261105].
-- MANTENHA BACKUP ANTES DE IMPORTAR. O banco informado no phpMyAdmin deve ser mat_temporis_curadoria.
SET NAMES utf8mb4;
START TRANSACTION;

-- A) Acrescenta roteiro objetivo nas justificativas SEM_EVIDENCIA que ja eram genericas.
-- Isso APARECE na Fila de revisao rapida, sem adicionar nova pagina ao site.
-- Nao preenche notas: apenas substitui instrucoes vagas por perguntas verificaveis.
UPDATE cur_criterios AS k
JOIN cur_candidatos AS c ON c.id_extracao=k.id_extracao
SET k.justificativa=CONCAT(
  COALESCE(k.justificativa,''),
  '\n[MAT-ROTEIRO-20261105] PROXIMA VERIFICACAO (orientacao; NAO e evidencia): ',
  CASE k.codigo
    WHEN 'impacto_difusao' THEN 'localizar locais, etapas e periodos de adocao, distinguindo primeira aparicao de difusao.'
    WHEN 'impacto_magnitude' THEN 'documentar mudancas observaveis antes/depois e um efeito causal verificavel; evitar fama como prova.'
    WHEN 'impacto_alcance' THEN 'identificar dominios geografico e social efetivamente atingidos, com fontes de cada area.'
    WHEN 'impacto_persistencia' THEN 'buscar exemplos datados de continuidade, substituicao ou abandono da pratica/dispositivo.'
    WHEN 'conf_rastreabilidade' THEN 'localizar documento primario ou estudo academico com pagina/DOI que comprove o evento delimitado.'
    WHEN 'conf_independencia' THEN 'confrontar no minimo duas referencias de procedencia independente; evitar fontes que apenas copiam a mesma cronologia.'
    WHEN 'conf_datacao' THEN 'determinar o que o ano significa (criacao, registro, publicacao ou difusao), com intervalo quando apropriado.'
    WHEN 'conf_consistencia' THEN 'comparar cronologias e descricoes conflitantes e registrar incertezas, com justificativa da interpretacao adotada.'
    WHEN 'fund_pervasividade' THEN 'comprovar aplicacoes distintas por setor, evitando extrapolar uma demonstracao local.'
    WHEN 'fund_aprimoramento' THEN 'identificar melhorias posteriores nomeadas e datadas e sua conexao com o evento analisado.'
    WHEN 'fund_complementaridade' THEN 'identificar dependencias ou complementaridades concretas com outras tecnicas e respectivas fontes.'
    ELSE 'identificar fonte independente e comprovar o criterio antes de atribuir nota.'
  END,
  CASE WHEN c.categoria IN ('CONTEXTO_HISTORICO','ESCOPO_DUVIDOSO','POSSIVEL_DUPLICATA')
       OR c.situacao_identificacao IN ('CONTEXTO_NAO_MARCO','ESCOPO_A_CONFIRMAR','POSSIVEL_DUPLICATA')
       THEN ' ATENCAO: primeiro decidir se constitui marco autonomo; nao forcar 11 notas num contexto.' ELSE '' END,
  ' Identificacao: ',LEFT(c.titulo_proposto,190),'. ',
  CASE WHEN c.ano_validado IS NULL THEN 'Data validada AUSENTE; nao usar ano extraido como data comprovada.'
       ELSE CONCAT('Ano registrado: ',CAST(c.ano_validado AS CHAR),'; conferir tipo de acontecimento datado.') END
)
WHERE k.nota IS NULL
  AND k.situacao='SEM_EVIDENCIA'
  AND c.estado_revisao_humana IN ('NAO_REVISADO','EM_REVISAO')
  AND COALESCE(k.justificativa,'') NOT LIKE '%[MAT-ROTEIRO-20261105]%'
  AND (
    k.justificativa IS NULL OR k.justificativa LIKE 'SEM_EVIDENCIA%'
    OR k.justificativa LIKE 'Sem comprova%' OR k.justificativa LIKE 'Sem evid%'
    OR k.justificativa LIKE 'Ainda sem base%'
  );

-- B) Torna rastreavel o capitulo da obra-base em fontes que ja continham paginacao.
-- A referencia original e preservada na integra; nao se afirma que capitulo prova pontuacao.
UPDATE cur_fontes AS f
JOIN cur_candidatos AS c ON c.id_extracao=f.id_extracao
SET f.descricao_evidencia = CONCAT(COALESCE(f.descricao_evidencia,''),
  ' [MAT-CAP-20261105] Localizacao por indice da obra de 2006: capitulo ',
  CASE
   WHEN c.pagina_livro < 17 THEN '1 - Humankind Emerges: Tools and Toolmakers'
   WHEN c.pagina_livro < 31 THEN '2 - The Reign of the Farmer'
   WHEN c.pagina_livro < 55 THEN '3 - Pharaohs and Engineers'
   WHEN c.pagina_livro < 79 THEN '4 - Greeks Bearing Gifts'
   WHEN c.pagina_livro < 97 THEN '5 - Alexandria and After'
   WHEN c.pagina_livro < 117 THEN '6 - The Enduring East'
   WHEN c.pagina_livro < 141 THEN '7 - The Middle Kingdom'
   WHEN c.pagina_livro < 155 THEN '8 - Indus, Ganges, and Beyond'
   WHEN c.pagina_livro < 177 THEN '9 - The New World'
   WHEN c.pagina_livro < 203 THEN '10 - Plows, Stirrups, Guns, and Plagues'
   WHEN c.pagina_livro < 223 THEN '11 - Copernicus Incites a Revolution'
   WHEN c.pagina_livro < 249 THEN '12 - The Crime and Punishment of Galileo Galilei'
   WHEN c.pagina_livro < 275 THEN '13 - God said, Let Newton be!'
   WHEN c.pagina_livro < 295 THEN '14 - Timber, Coal, Cloth, and Steam'
   WHEN c.pagina_livro < 323 THEN '15 - Legacies of Revolution'
   WHEN c.pagina_livro < 339 THEN '16 - Life Itself'
   WHEN c.pagina_livro < 365 THEN '17 - Toolmakers Take Command'
   WHEN c.pagina_livro < 391 THEN '18 - The New Aristotelians'
   WHEN c.pagina_livro < 415 THEN '19 - The Bomb and the Genome'
   WHEN c.pagina_livro < 437 THEN '20 - Under Todays Pharaohs'
   ELSE 'Conclusao - The Medium of History'
  END,
  ', pagina impressa ',CAST(c.pagina_livro AS CHAR),
  ', pagina PDF ',COALESCE(CAST(c.pagina_pdf AS CHAR),'nao disponivel'),
  '. Menção inicial, NAO validacao automatica do marco.'
)
WHERE f.fonte_id='B2006'
 AND c.pagina_livro BETWEEN 5 AND 437
 AND c.estado_revisao_humana IN ('NAO_REVISADO','EM_REVISAO')
 AND COALESCE(f.descricao_evidencia,'') NOT LIKE '%[MAT-CAP-20261105]%';

-- C) Uma nova referencia academica verificada para o parafuso hidraulico.
-- O artigo discute alternativas cronologicas, nao comprova data exata de invencao.
INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza)
SELECT 'MCD-00019','REV_PUMPS_2015','Stavros I. Yannopoulos et al.',
'Evolution of Water Lifting Devices (Pumps) over the Centuries Worldwide','2015',
'Artigo historico refere o parafuso hidraulico e discute a controvérsia sobre sua atribuição a Arquimedes e possiveis antecedentes mesopotamicos. Nao demonstra -234 como ano de invencao.',
'https://www.mdpi.com/2073-4441/7/9/5031','ARTIGO_ACADEMICO_SECUNDARIO'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM cur_candidatos WHERE id_extracao='MCD-00019')
AND NOT EXISTS (SELECT 1 FROM cur_fontes WHERE id_extracao='MCD-00019' AND fonte_id='REV_PUMPS_2015');

-- C2) Referencias adicionais para dois dossies que tinham somente o livro-base.
-- Usadas como caminhos documentais; NAO homologam ano, escopo ou rubricas.
INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza)
SELECT 'MCD-00008','REV_MET_ALFAB_2014','Elizabeth Knott / The Metropolitan Museum of Art',
'Alphabet Origins: From Kipling to Sinai','2014',
'O museu distingue origem alfabetica anterior, uso fenicio e difusao pelo Mediterraneo; nao sustenta 1100 a.C. como data exata unica de invencao.',
'https://www.metmuseum.org/exhibitions/listings/2014/assyria-to-iberia/blog/posts/alphabet',
'MUSEU_FONTE_SECUNDARIA' FROM DUAL
WHERE EXISTS (SELECT 1 FROM cur_candidatos WHERE id_extracao='MCD-00008')
AND NOT EXISTS (SELECT 1 FROM cur_fontes WHERE id_extracao='MCD-00008' AND fonte_id='REV_MET_ALFAB_2014');
INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza)
SELECT 'MCD-00008','REV_WAAL_2018','Willemijn Waal',
'On the Phoenician letters: the case for an early transmission of the Greek alphabet','2018',
'Artigo discute controversias cronologicas na transmissao ao grego e a insuficiencia de uma data pontual universal.',
'https://www.aegeussociety.org/studies/on-the-phoenician-letters-the-case-for-an-early-transmission-of-the-greek-alphabet-from-an-archaeological-epigraphic-and-linguistic-perspective/',
'ARTIGO_ACADEMICO_SECUNDARIO' FROM DUAL
WHERE EXISTS (SELECT 1 FROM cur_candidatos WHERE id_extracao='MCD-00008')
AND NOT EXISTS (SELECT 1 FROM cur_fontes WHERE id_extracao='MCD-00008' AND fonte_id='REV_WAAL_2018');
INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza)
SELECT 'MCD-00211','REV_SCI_MUSEUM_1784','Science Museum Group',
'Watt''s Parallel Motion, 1784','1784',
'Acervo do museu identifica o mecanismo de movimento paralelo de Watt com ano 1784; trata-se da estrutura, nao de todas as melhorias da maquina a vapor.',
'https://collection.sciencemuseumgroup.org.uk/objects/co50729/watts-parallel-motion-1784',
'ACERVO_MUSEOLOGICO' FROM DUAL
WHERE EXISTS (SELECT 1 FROM cur_candidatos WHERE id_extracao='MCD-00211')
AND NOT EXISTS (SELECT 1 FROM cur_fontes WHERE id_extracao='MCD-00211' AND fonte_id='REV_SCI_MUSEUM_1784');
INSERT INTO cur_fontes (id_extracao,fonte_id,autor_instituicao,titulo,ano,descricao_evidencia,url,natureza)
SELECT 'MCD-00211','REV_ASME_WATT_1785','American Society of Mechanical Engineers',
'Boulton & Watt Rotative Steam Engine','1785',
'ASME documenta motor rotativo de 1785 e desenho de novembro de 1784 contendo mecanismo paralelo; distingue modelo e demonstracao.',
'https://www.asme.org/about-asme/engineering-history/landmarks/111-boulton-watt-rotative-steam-engine',
'SOCIEDADE_PROFISSIONAL_HISTORIA_TECNICA' FROM DUAL
WHERE EXISTS (SELECT 1 FROM cur_candidatos WHERE id_extracao='MCD-00211')
AND NOT EXISTS (SELECT 1 FROM cur_fontes WHERE id_extracao='MCD-00211' AND fonte_id='REV_ASME_WATT_1785');

-- D) Quatro alertas documentais confirmados para ajudar a conferir datas/escopo.
-- Mantem as notas historicas ja digitadas, e NAO resolve pendencias.
UPDATE cur_criterios SET o_que_falta=CONCAT(COALESCE(o_que_falta,''),
 ' [MAT-DATAS-20261105] A obra-base apresenta atribuicao contestada ao seculo III a.C.; ano -234 NAO deve ser tratado como data comprovada de invencao. Verificar datação intervalar e evidencias primarias.')
WHERE id_extracao='MCD-00019' AND codigo='conf_datacao'
AND COALESCE(o_que_falta,'') NOT LIKE '%[MAT-DATAS-20261105]%';
UPDATE cur_criterios SET o_que_falta=CONCAT(COALESCE(o_que_falta,''),
 ' [MAT-DATAS-20261105] Ministerio egipcio situa reinado de Queops em c. 2589-2566 a.C.; -2570 pode ser ponto estimado, nao o ano exato de inicio/conclusao da Grande Piramide.')
WHERE id_extracao='MCD-00007' AND codigo='conf_datacao'
AND COALESCE(o_que_falta,'') NOT LIKE '%[MAT-DATAS-20261105]%';
UPDATE cur_criterios SET o_que_falta=CONCAT(COALESCE(o_que_falta,''),
 ' [MAT-DATAS-20261105] British Library confirma exemplar de Sutra do Diamante datado 11/05/868; isso nao e a invencao da xilografia. Pontuar apenas o evento especifico definido.')
WHERE id_extracao='MCD-00040' AND codigo='conf_datacao'
AND COALESCE(o_que_falta,'') NOT LIKE '%[MAT-DATAS-20261105]%';
UPDATE cur_criterios SET o_que_falta=CONCAT(COALESCE(o_que_falta,''),
 ' [MAT-DATAS-20261105] Royal Society confirma lancamento do primeiro Philosophical Transactions em março de 1665; efeitos posteriores requerem fontes adicionais.')
WHERE id_extracao='MCD-00147' AND codigo='conf_datacao'
AND COALESCE(o_que_falta,'') NOT LIKE '%[MAT-DATAS-20261105]%';

-- E) Log de acao tecnica: NAO uma aprovacao, decisoes continuam intocadas.
INSERT INTO cur_log (id_extracao,acao,descricao)
SELECT NULL,'ENRIQUECIMENTO_ORIENTATIVO_20261105',
'Acrescentadas orientacoes de revisao a criterios sem nota; paginas de livro localizadas; cinco fontes adicionais; alertas pontuais. Nenhuma nota, data validada, pendencia, aprovacao ou homologacao alterada.'
FROM DUAL WHERE NOT EXISTS (
 SELECT 1 FROM cur_log WHERE acao='ENRIQUECIMENTO_ORIENTATIVO_20261105');
COMMIT;

-- Conferencia posterior (somente leitura):
SELECT 'CRITERIOS_COM_ROTEIRO' AS indicador,COUNT(*) AS quantidade
FROM cur_criterios WHERE justificativa LIKE '%[MAT-ROTEIRO-20261105]%'
UNION ALL SELECT 'FONTES_COM_CAPITULO',COUNT(*) FROM cur_fontes WHERE descricao_evidencia LIKE '%[MAT-CAP-20261105]%'
UNION ALL SELECT 'NOVAS_FONTES_CONSULTAVEIS',COUNT(*) FROM cur_fontes WHERE fonte_id IN ('REV_PUMPS_2015','REV_MET_ALFAB_2014','REV_WAAL_2018','REV_SCI_MUSEUM_1784','REV_ASME_WATT_1785')
UNION ALL SELECT 'CANDIDATOS',COUNT(*) FROM cur_candidatos
UNION ALL SELECT 'NOTAS_PREENCHIDAS',COUNT(*) FROM cur_criterios WHERE nota IS NOT NULL;
