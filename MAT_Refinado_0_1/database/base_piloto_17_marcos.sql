-- MAT | BASE PILOTO DOCUMENTAL DE 17 MARCOS | 2026-10-26
-- Compatível com MAT Observatório v6.2 (schema.sql + update_v6_2.sql).
-- ESTE ARQUIVO NÃO APAGA DADOS, NÃO CONCEDE NOTAS E NÃO ATIVA O ÍNDICE.
-- FAÇA BACKUP DE mat_observatorio ANTES DE EXECUTAR.
-- Rode somente após instalar o esquema COMPLETO da v6.2.
USE mat_observatorio;
START TRANSACTION;

-- -3300: Registros protocuneiformes na Mesopotâmia
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Cuneiform tablet: administrative account with entries concerning malt and barley groats', 'The Metropolitan Museum of Art', 'instituicao', 'https://www.metmuseum.org/art/collection/search/327385', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.metmuseum.org/art/collection/search/327385');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Registros protocuneiformes na Mesopotâmia', -3300, 'aproximada', 'Antiguidade', 'Comunicação', 'Tabuletas administrativas do fim do quarto milênio a.C. documentam o emprego inicial de sinais gráficos e numerais para registrar bens e operações econômicas.', 'Marco situado aproximadamente em 3300 a.C.; não afirmar ser a primeira escrita em todos os lugares.', 'Sul da Mesopotâmia (atual Iraque)', NULL, 'The Metropolitan Museum of Art — Cuneiform tablet: administrative account with entries concerning malt and barley groats', 'https://www.metmuseum.org/art/collection/search/327385', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.metmuseum.org/art/collection/search/327385'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Registros protocuneiformes na Mesopotâmia' AND m.ano=-3300);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.metmuseum.org/art/collection/search/327385'
WHERE m.titulo='Registros protocuneiformes na Mesopotâmia' AND m.ano=-3300;

-- 1377: Impressão do Jikji com tipos metálicos móveis
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Jikji, a treasure of the world of printing', 'Bibliothèque nationale de France', 'instituicao', 'https://www.bnf.fr/en/jikji-treasure-world-printing', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.bnf.fr/en/jikji-treasure-world-printing');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Impressão do Jikji com tipos metálicos móveis', 1377, 'exata', 'Idade Média', 'Comunicação', 'O Jikji é um livro budista impresso em 1377 com tipos metálicos móveis na Coreia; um exemplar incompleto está preservado na Biblioteca Nacional da França.', 'Não é necessariamente a invenção inicial dos tipos móveis; é o exemplar preservado e documentado em questão.', 'Cheongju, Coreia', 'Oficina do templo Heungdeok-sa', 'Bibliothèque nationale de France — Jikji, a treasure of the world of printing', 'https://www.bnf.fr/en/jikji-treasure-world-printing', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.bnf.fr/en/jikji-treasure-world-printing'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Impressão do Jikji com tipos metálicos móveis' AND m.ano=1377);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.bnf.fr/en/jikji-treasure-world-printing'
WHERE m.titulo='Impressão do Jikji com tipos metálicos móveis' AND m.ano=1377;

-- 1455: Impressão da Bíblia de Gutenberg em Mainz
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'The Gutenberg Bible', 'Library of Congress', 'instituicao', 'https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Impressão da Bíblia de Gutenberg em Mainz', 1455, 'aproximada', 'Idade Moderna', 'Comunicação', 'A Bíblia impressa em Mainz por volta de 1455 exemplifica a impressão tipográfica em grande escala com tipos metálicos móveis na Europa ocidental.', 'Não descrever como primeiro livro impresso do mundo; houve impressão metálica móvel anterior na Coreia.', 'Mainz, atual Alemanha', 'Johannes Gutenberg e Johannes Fust', 'Library of Congress — The Gutenberg Bible', 'https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Impressão da Bíblia de Gutenberg em Mainz' AND m.ano=1455);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html'
WHERE m.titulo='Impressão da Bíblia de Gutenberg em Mainz' AND m.ano=1455;

-- 1712: Máquina atmosférica de Thomas Newcomen
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Demainbray''s Newcomen engine model', 'Science Museum Group', 'instituicao', 'https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Máquina atmosférica de Thomas Newcomen', 1712, 'exata', 'Idade Moderna', 'Energia', 'Thomas Newcomen projetou em 1712 um motor atmosférico que utilizava vapor e condensação para acionar bombas de drenagem em minas.', 'A máquina de Newcomen não deve ser confundida com os aprimoramentos posteriores de James Watt.', 'Inglaterra', 'Thomas Newcomen', 'Science Museum Group — Demainbray''s Newcomen engine model', 'https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Máquina atmosférica de Thomas Newcomen' AND m.ano=1712);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model'
WHERE m.titulo='Máquina atmosférica de Thomas Newcomen' AND m.ano=1712;

-- 1796: Primeira vacinação contra a varíola por Edward Jenner
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'A brief history of vaccination', 'Organização Mundial da Saúde (OMS)', 'instituicao', 'https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Primeira vacinação contra a varíola por Edward Jenner', 1796, 'exata', 'Era Industrial', 'Medicina', 'Em 1796, Edward Jenner demonstrou a proteção contra varíola por inoculação com material de cowpox, marco da vacinação moderna.', 'Práticas anteriores de variolização existiam e devem ser diferenciadas da vacinação de Jenner.', 'Inglaterra', 'Edward Jenner', 'Organização Mundial da Saúde (OMS) — A brief history of vaccination', 'https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Primeira vacinação contra a varíola por Edward Jenner' AND m.ano=1796);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination'
WHERE m.titulo='Primeira vacinação contra a varíola por Edward Jenner' AND m.ano=1796;

-- 1844: Demonstração pública do telégrafo elétrico de Morse
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'First telegraph message, 24 May 1844', 'Library of Congress', 'instituicao', 'https://www.loc.gov/item/mcc.019/', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.loc.gov/item/mcc.019/');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Demonstração pública do telégrafo elétrico de Morse', 1844, 'exata', 'Era Industrial', 'Comunicação', 'Morse enviou uma mensagem telegráfica demonstrativa de Washington a Baltimore em 24 de maio de 1844, registrada em fita preservada.', 'Marco de demonstração pública do sistema, não a origem de toda comunicação elétrica.', 'Washington e Baltimore, Estados Unidos', 'Samuel F. B. Morse e Alfred Vail', 'Library of Congress — First telegraph message, 24 May 1844', 'https://www.loc.gov/item/mcc.019/', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.loc.gov/item/mcc.019/'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Demonstração pública do telégrafo elétrico de Morse' AND m.ano=1844);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.loc.gov/item/mcc.019/'
WHERE m.titulo='Demonstração pública do telégrafo elétrico de Morse' AND m.ano=1844;

-- 1876: Transmissão de fala inteligível pelo telefone de Bell
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Telephones Through Time: Smithsonian''s Historic Collection', 'Smithsonian Institution', 'instituicao', 'https://avpreservation.si.edu/spotlight/phones', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://avpreservation.si.edu/spotlight/phones');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Transmissão de fala inteligível pelo telefone de Bell', 1876, 'exata', 'Era Industrial', 'Comunicação', 'Em 10 de março de 1876, Alexander Graham Bell realizou uma das primeiras transmissões bem-sucedidas de voz inteligível por telefone.', 'Marco de demonstração de Bell; a história da invenção da telefonia envolve outras contribuições.', 'Estados Unidos', 'Alexander Graham Bell', 'Smithsonian Institution — Telephones Through Time: Smithsonian''s Historic Collection', 'https://avpreservation.si.edu/spotlight/phones', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://avpreservation.si.edu/spotlight/phones'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Transmissão de fala inteligível pelo telefone de Bell' AND m.ano=1876);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://avpreservation.si.edu/spotlight/phones'
WHERE m.titulo='Transmissão de fala inteligível pelo telefone de Bell' AND m.ano=1876;

-- 1903: Voos controlados e motorizados dos irmãos Wright
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT '1903 Wright Flyer', 'Smithsonian Institution', 'instituicao', 'https://www.si.edu/object/nasm_A19610048000', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.si.edu/object/nasm_A19610048000');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Voos controlados e motorizados dos irmãos Wright', 1903, 'exata', 'Era Industrial', 'Engenharia', 'Em 17 de dezembro de 1903, os irmãos Wright realizaram voos sustentados, controlados e motorizados com uma aeronave mais pesada que o ar.', 'A descrição deve manter as condições técnicas: controle, propulsão e sustentação; não apenas primeiro objeto a voar.', 'Kitty Hawk, Carolina do Norte, Estados Unidos', 'Wilbur e Orville Wright', 'Smithsonian Institution — 1903 Wright Flyer', 'https://www.si.edu/object/nasm_A19610048000', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.si.edu/object/nasm_A19610048000'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Voos controlados e motorizados dos irmãos Wright' AND m.ano=1903);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.si.edu/object/nasm_A19610048000'
WHERE m.titulo='Voos controlados e motorizados dos irmãos Wright' AND m.ano=1903;

-- 1928: Descoberta da penicilina por Alexander Fleming
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Alexander Fleming''s Discovery and Development of Penicillin', 'American Chemical Society', 'instituicao', 'https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Descoberta da penicilina por Alexander Fleming', 1928, 'exata', 'Era Industrial', 'Medicina', 'Alexander Fleming identificou em 1928 o efeito antibacteriano associado ao fungo Penicillium; a aplicação clínica e a produção em escala ocorreram posteriormente.', 'Não confundir a descoberta de 1928 com o desenvolvimento terapêutico posterior por Florey, Chain e colaboradores.', 'Londres, Reino Unido', 'Alexander Fleming', 'American Chemical Society — Alexander Fleming''s Discovery and Development of Penicillin', 'https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Descoberta da penicilina por Alexander Fleming' AND m.ano=1928);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html'
WHERE m.titulo='Descoberta da penicilina por Alexander Fleming' AND m.ano=1928;

-- 1946: Apresentação pública do computador ENIAC
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT '1946: Public unveiling of ENIAC', 'Computer History Museum', 'instituicao', 'https://www.computerhistory.org/timeline/1946/', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.computerhistory.org/timeline/1946/');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Apresentação pública do computador ENIAC', 1946, 'exata', 'Era Digital', 'Computação', 'O ENIAC foi apresentado publicamente em 1946 como um dos primeiros computadores digitais eletrônicos de grande escala.', 'Não reivindicar que foi o primeiro computador de qualquer tipo; existem definições e predecessores distintos.', 'Universidade da Pensilvânia, Estados Unidos', 'J. Presper Eckert, John Mauchly e equipe', 'Computer History Museum — 1946: Public unveiling of ENIAC', 'https://www.computerhistory.org/timeline/1946/', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.computerhistory.org/timeline/1946/'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Apresentação pública do computador ENIAC' AND m.ano=1946);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.computerhistory.org/timeline/1946/'
WHERE m.titulo='Apresentação pública do computador ENIAC' AND m.ano=1946;

-- 1947: Demonstração do transistor de contato pontual
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Inventing the Transistor', 'Computer History Museum', 'instituicao', 'https://www.computerhistory.org/revolution/story/273', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.computerhistory.org/revolution/story/273');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Demonstração do transistor de contato pontual', 1947, 'exata', 'Era Digital', 'Computação', 'Em 1947, pesquisadores dos Bell Labs demonstraram um transistor semicondutor de contato pontual capaz de amplificar sinais.', 'Diferenciar transistor de contato pontual (1947) de transistor de junção (posterior).', 'Bell Labs, Estados Unidos', 'John Bardeen e Walter Brattain, equipe de William Shockley', 'Computer History Museum — Inventing the Transistor', 'https://www.computerhistory.org/revolution/story/273', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.computerhistory.org/revolution/story/273'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Demonstração do transistor de contato pontual' AND m.ano=1947);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.computerhistory.org/revolution/story/273'
WHERE m.titulo='Demonstração do transistor de contato pontual' AND m.ano=1947;

-- 1958: Demonstração de circuito integrado por Jack Kilby
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Integrated Circuit by Jack Kilby', 'Smithsonian Institution', 'instituicao', 'https://www.si.edu/object/nmah_689592', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.si.edu/object/nmah_689592');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Demonstração de circuito integrado por Jack Kilby', 1958, 'exata', 'Era Digital', 'Computação', 'Em 1958, Jack Kilby demonstrou um circuito integrado fabricado em uma peça de germânio, combinando componentes eletrônicos em um mesmo substrato.', 'Marco demonstrativo de Kilby; não atribuir exclusivamente a ele todas as arquiteturas de circuitos integrados modernos.', 'Texas Instruments, Estados Unidos', 'Jack Kilby', 'Smithsonian Institution — Integrated Circuit by Jack Kilby', 'https://www.si.edu/object/nmah_689592', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.si.edu/object/nmah_689592'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Demonstração de circuito integrado por Jack Kilby' AND m.ano=1958);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.si.edu/object/nmah_689592'
WHERE m.titulo='Demonstração de circuito integrado por Jack Kilby' AND m.ano=1958;

-- 1969: Operação inicial da rede ARPANET com quatro nós
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'ARPANET', 'DARPA', 'instituicao', 'https://www.darpa.mil/news/features/arpanet', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.darpa.mil/news/features/arpanet');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Operação inicial da rede ARPANET com quatro nós', 1969, 'exata', 'Era Digital', 'Comunicação', 'Em 1969, a ARPANET passou a conectar quatro instituições de pesquisa nos Estados Unidos, estabelecendo um antecedente importante da internet.', 'ARPANET não é sinônimo de World Wide Web; distinguir rede e aplicação Web.', 'Estados Unidos', 'ARPA e instituições participantes', 'DARPA — ARPANET', 'https://www.darpa.mil/news/features/arpanet', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.darpa.mil/news/features/arpanet'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Operação inicial da rede ARPANET com quatro nós' AND m.ano=1969);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.darpa.mil/news/features/arpanet'
WHERE m.titulo='Operação inicial da rede ARPANET com quatro nós' AND m.ano=1969;

-- 1971: Lançamento comercial do microprocessador Intel 4004
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Intel 4004 Microprocessor', 'Smithsonian Institution', 'instituicao', 'https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Lançamento comercial do microprocessador Intel 4004', 1971, 'exata', 'Era Digital', 'Computação', 'A Intel lançou o 4004 em 15 de novembro de 1971, um microprocessador comercial que reunia as funções essenciais de CPU em um chip.', 'Especificar lançamento comercial do Intel 4004, evitando generalização sobre toda a história do processador.', 'Estados Unidos', 'Federico Faggin, Ted Hoff, Stan Mazor e Masatoshi Shima', 'Smithsonian Institution — Intel 4004 Microprocessor', 'https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Lançamento comercial do microprocessador Intel 4004' AND m.ano=1971);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495'
WHERE m.titulo='Lançamento comercial do microprocessador Intel 4004' AND m.ano=1971;

-- 1989: Proposta inicial da World Wide Web no CERN
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'A short history of the Web', 'CERN', 'instituicao', 'https://home.cern/science/computing/the-birth-of-the-web/short-history-web/', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://home.cern/science/computing/the-birth-of-the-web/short-history-web/');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Proposta inicial da World Wide Web no CERN', 1989, 'exata', 'Era Digital', 'Computação', 'Em março de 1989, Tim Berners-Lee propôs um sistema de informação baseado em hipertexto conectado, que originou a World Wide Web.', 'O primeiro navegador e servidor surgiram em 1990; não confundir proposta com disponibilização pública posterior.', 'CERN, Suíça', 'Tim Berners-Lee', 'CERN — A short history of the Web', 'https://home.cern/science/computing/the-birth-of-the-web/short-history-web/', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://home.cern/science/computing/the-birth-of-the-web/short-history-web/'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Proposta inicial da World Wide Web no CERN' AND m.ano=1989);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://home.cern/science/computing/the-birth-of-the-web/short-history-web/'
WHERE m.titulo='Proposta inicial da World Wide Web no CERN' AND m.ano=1989;

-- 2003: Conclusão anunciada do Projeto Genoma Humano
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'Human Genome Project Fact Sheet', 'National Human Genome Research Institute (NIH)', 'instituicao', 'https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Conclusão anunciada do Projeto Genoma Humano', 2003, 'exata', 'Era Contemporânea', 'Ciência', 'Em 2003, o Projeto Genoma Humano anunciou uma sequência de referência essencialmente completa, ainda com lacunas em regiões complexas.', 'Não afirmar que a sequência de 2003 não continha lacunas; maior completude veio em trabalhos posteriores.', 'Consórcio internacional', 'International Human Genome Sequencing Consortium', 'National Human Genome Research Institute (NIH) — Human Genome Project Fact Sheet', 'https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Conclusão anunciada do Projeto Genoma Humano' AND m.ano=2003);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project'
WHERE m.titulo='Conclusão anunciada do Projeto Genoma Humano' AND m.ano=2003;

-- 2012: Demonstração do sistema CRISPR-Cas9 programável
INSERT INTO fontes (titulo, autores, tipo, url, notas)
SELECT 'CRISPR-Cas9 wins Nobel (Strzyz, 2020)', 'Nature Reviews Molecular Cell Biology', 'artigo', 'https://www.nature.com/articles/s41580-020-00307-9', 'Referência de base piloto; revisar conteúdo completo antes de homologar peso/impacto.'
WHERE NOT EXISTS (SELECT 1 FROM fontes WHERE url='https://www.nature.com/articles/s41580-020-00307-9');
INSERT INTO marcos (titulo, ano, precisao_data, periodo, categoria, descricao, detalhes, local, criador, fonte, referencia_url, criterio_inclusao, etiquetas, status, incluir_temporis, elegibilidade_temporis, escopo_revisado, fonte_id)
SELECT 'Demonstração do sistema CRISPR-Cas9 programável', 2012, 'exata', 'Era Contemporânea', 'Medicina', 'Em 2012, foi publicado um trabalho seminal sobre o uso programável do sistema CRISPR-Cas9 para corte direcionado de DNA, base de métodos modernos de edição genética.', 'Marco metodológico de 2012; os sistemas naturais CRISPR e estudos anteriores são distintos desse protocolo.', 'Pesquisa internacional', 'Jennifer Doudna, Emmanuelle Charpentier e colaboradores', 'Nature Reviews Molecular Cell Biology — CRISPR-Cas9 wins Nobel (Strzyz, 2020)', 'https://www.nature.com/articles/s41580-020-00307-9', 'Candidato de base piloto: ocorrência e data documentadas em fonte identificada. Pontuação de impacto, escopo e elegibilidade dependem de revisão independente.', 'base_piloto_17;revisao_pendente', 'pendente', 0, 'revisar', 0, f.id
FROM fontes f WHERE f.url='https://www.nature.com/articles/s41580-020-00307-9'
AND NOT EXISTS (SELECT 1 FROM marcos m WHERE m.titulo='Demonstração do sistema CRISPR-Cas9 programável' AND m.ano=2012);
INSERT IGNORE INTO marco_fontes (marco_id, fonte_id, papel)
SELECT m.id, f.id, 'origem' FROM marcos m JOIN fontes f ON f.url='https://www.nature.com/articles/s41580-020-00307-9'
WHERE m.titulo='Demonstração do sistema CRISPR-Cas9 programável' AND m.ano=2012;

COMMIT;

-- CONFERÊNCIA APÓS IMPORTAÇÃO:
-- SELECT COUNT(*) FROM marcos WHERE etiquetas LIKE '%base_piloto_17%';
-- SELECT status, COUNT(*) FROM marcos WHERE etiquetas LIKE '%base_piloto_17%' GROUP BY status;
-- SELECT COUNT(*) FROM marcos WHERE incluir_temporis = 1 AND etiquetas LIKE '%base_piloto_17%';
