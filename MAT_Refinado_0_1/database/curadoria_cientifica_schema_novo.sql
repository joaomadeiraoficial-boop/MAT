-- MAT / Curadoria Científica — esquema vazio, NÃO importa os 300 candidatos.
-- NÃO EXECUTE EM BANCO DE PRODUÇÃO EXISTENTE SEM BACKUP E COMPARAÇÃO DO ESQUEMA.
-- Para instalação inicial em banco vazio. Revise usuário/permissões antes de executar.
CREATE DATABASE IF NOT EXISTS mat_temporis_curadoria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mat_temporis_curadoria;

CREATE TABLE IF NOT EXISTS cur_rodadas (
 numero SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
 estado VARCHAR(80) NOT NULL DEFAULT 'PLANEJADA',
 observacao TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cur_candidatos (
 id_extracao VARCHAR(20) NOT NULL PRIMARY KEY,
 rodada SMALLINT UNSIGNED NOT NULL,
 titulo_proposto VARCHAR(255) NOT NULL,
 titulo_original LONGTEXT NULL,
 ano_original INT NULL,
 ano_validado INT NULL,
 pagina_livro VARCHAR(60) NULL,
 pagina_pdf VARCHAR(60) NULL,
 periodo_historico VARCHAR(120) NULL,
 categoria VARCHAR(100) NULL,
 situacao_identificacao VARCHAR(90) NULL,
 observacao_metodologica TEXT NULL,
 status_avaliacao VARCHAR(90) NOT NULL DEFAULT 'NAO_INICIADO',
 estado_revisao_humana VARCHAR(30) NOT NULL DEFAULT 'NAO_REVISADO',
 homologado TINYINT(1) NOT NULL DEFAULT 0,
 analise_historica_json LONGTEXT NULL,
 pendencias_json LONGTEXT NULL,
 duplicatas_json LONGTEXT NULL,
 INDEX idx_cur_rodada (rodada),
 INDEX idx_cur_status (status_avaliacao, estado_revisao_humana),
 CONSTRAINT fk_cur_rodada FOREIGN KEY (rodada) REFERENCES cur_rodadas(numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cur_criterios (
 id_extracao VARCHAR(20) NOT NULL,
 codigo VARCHAR(55) NOT NULL,
 nota TINYINT UNSIGNED NULL,
 situacao VARCHAR(90) NOT NULL DEFAULT 'SEM_EVIDENCIA',
 justificativa TEXT NULL,
 evidencias_json LONGTEXT NULL,
 fontes_ids_json LONGTEXT NULL,
 o_que_falta TEXT NULL,
 PRIMARY KEY (id_extracao, codigo),
 CONSTRAINT fk_cur_criterio_candidato FOREIGN KEY (id_extracao) REFERENCES cur_candidatos(id_extracao) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cur_fontes (
 id_extracao VARCHAR(20) NOT NULL,
 fonte_id VARCHAR(48) NOT NULL,
 autor_instituicao VARCHAR(180) NOT NULL,
 titulo VARCHAR(255) NOT NULL,
 ano INT NULL,
 descricao_evidencia TEXT NULL,
 url VARCHAR(500) NULL,
 natureza VARCHAR(90) NULL,
 PRIMARY KEY (id_extracao, fonte_id),
 CONSTRAINT fk_cur_fonte_candidato FOREIGN KEY (id_extracao) REFERENCES cur_candidatos(id_extracao) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cur_log (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 id_extracao VARCHAR(20) NULL,
 acao VARCHAR(100) NOT NULL,
 descricao LONGTEXT NULL,
 data_acao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_cur_log_recente(data_acao,id),
 INDEX idx_cur_log_id(id_extracao),
 CONSTRAINT fk_cur_log_candidato FOREIGN KEY (id_extracao) REFERENCES cur_candidatos(id_extracao) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Não insere IDs, fontes ou notas: a fonte original do pesquisador é indispensável.
