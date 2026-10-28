-- MAT Observatorio: esquema independente; NAO altera o banco mat_v5.
CREATE DATABASE IF NOT EXISTS mat_observatorio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mat_observatorio;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(110) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fontes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(255) NOT NULL,
 autores VARCHAR(255) DEFAULT NULL,
 ano_publicacao SMALLINT DEFAULT NULL,
 tipo ENUM('livro','artigo','instituicao','catalogo','outra') NOT NULL DEFAULT 'outra',
 url VARCHAR(650) DEFAULT NULL,
 editora VARCHAR(200) DEFAULT NULL,
 notas TEXT DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_lotes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 arquivo_nome VARCHAR(255) NOT NULL,
 arquivo_sha256 CHAR(64) NOT NULL,
 total_itens INT UNSIGNED NOT NULL DEFAULT 0,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_lote_hash (arquivo_sha256)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marcos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(250) NOT NULL,
 ano INT NOT NULL,
 ano_fim INT DEFAULT NULL,
 precisao_data ENUM('exata','aproximada','intervalo','incerta') NOT NULL DEFAULT 'aproximada',
 periodo VARCHAR(90) NOT NULL,
 categoria VARCHAR(90) NOT NULL DEFAULT 'Outros',
 descricao TEXT NOT NULL,
 detalhes MEDIUMTEXT DEFAULT NULL,
 local VARCHAR(180) DEFAULT NULL,
 civilizacao VARCHAR(180) DEFAULT NULL,
 criador VARCHAR(180) DEFAULT NULL,
 fonte VARCHAR(350) DEFAULT NULL,
 referencia_url VARCHAR(650) DEFAULT NULL,
 fonte_pagina VARCHAR(90) DEFAULT NULL,
 criterio_inclusao TEXT DEFAULT NULL,
 etiquetas VARCHAR(400) DEFAULT NULL,
 imagem_url VARCHAR(650) DEFAULT NULL,
 impacto DECIMAL(3,1) DEFAULT NULL,
 confiabilidade DECIMAL(3,1) DEFAULT NULL,
 fator_fundacional DECIMAL(3,2) DEFAULT NULL,
 status ENUM('demonstracao','importado','pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
 natureza VARCHAR(40) NOT NULL DEFAULT 'nao_classificado',
 sugestao_elegibilidade ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
 elegibilidade_temporis ENUM('sim','nao','revisar') NOT NULL DEFAULT 'revisar',
 motivo_escopo TEXT DEFAULT NULL,
 escopo_revisado TINYINT(1) NOT NULL DEFAULT 0,
 classificador_versao VARCHAR(40) DEFAULT NULL,
 scope_score INT NOT NULL DEFAULT 0,
 scope_confidence VARCHAR(12) NOT NULL DEFAULT 'baixa',
 incluir_temporis TINYINT(1) NOT NULL DEFAULT 0,
 fonte_id INT UNSIGNED DEFAULT NULL,
 import_item_id INT UNSIGNED DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_marcos_ano(ano), INDEX idx_marcos_periodo(periodo),
 INDEX idx_marcos_categoria(categoria), INDEX idx_marcos_status(status),
 UNIQUE KEY uq_marco_titulo_ano(titulo,ano),
 CONSTRAINT fk_marcos_fontes FOREIGN KEY(fonte_id) REFERENCES fontes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_itens (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 lote_id INT UNSIGNED NOT NULL,
 numero_linha INT NOT NULL,
 bruto_json LONGTEXT NOT NULL,
 titulo VARCHAR(250) DEFAULT NULL,
 ano INT DEFAULT NULL,
 categoria VARCHAR(90) DEFAULT NULL,
 descricao TEXT DEFAULT NULL,
 fonte VARCHAR(350) DEFAULT NULL,
 status ENUM('pendente','convertido','duplicado','invalido') NOT NULL DEFAULT 'pendente',
 marco_id INT UNSIGNED DEFAULT NULL,
 erro VARCHAR(350) DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_lote_linha(lote_id,numero_linha),
 CONSTRAINT fk_itens_lote FOREIGN KEY(lote_id) REFERENCES import_lotes(id) ON DELETE CASCADE,
 CONSTRAINT fk_itens_marco FOREIGN KEY(marco_id) REFERENCES marcos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS revisoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 marco_id INT UNSIGNED NOT NULL,
 usuario_id INT UNSIGNED DEFAULT NULL,
 operacao VARCHAR(50) NOT NULL,
 dados_anteriores LONGTEXT DEFAULT NULL,
 dados_novos LONGTEXT DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_revisoes_marco (marco_id),
 CONSTRAINT fk_revisoes_marco FOREIGN KEY(marco_id) REFERENCES marcos(id) ON DELETE CASCADE,
 CONSTRAINT fk_revisoes_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Complementos do laboratorio v6: as fontes sao vinculadas de forma rastreavel.
CREATE TABLE IF NOT EXISTS marco_fontes (
  marco_id INT UNSIGNED NOT NULL,
  fonte_id INT UNSIGNED NOT NULL,
  papel ENUM('origem','confirmacao','data','impacto','fundacional','confiabilidade','contexto') NOT NULL DEFAULT 'confirmacao',
  trecho TEXT DEFAULT NULL,
  pagina VARCHAR(70) DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (marco_id, fonte_id, papel),
  CONSTRAINT fk_mf_obs_marco FOREIGN KEY (marco_id) REFERENCES marcos(id) ON DELETE CASCADE,
  CONSTRAINT fk_mf_obs_fonte FOREIGN KEY (fonte_id) REFERENCES fontes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS duplicate_candidates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 marco_a INT UNSIGNED NOT NULL,
 marco_b INT UNSIGNED NOT NULL,
 similarity DECIMAL(5,4) NOT NULL,
 status ENUM('pendente','nao_duplicado','revisao_manual') NOT NULL DEFAULT 'pendente',
 revisado_em DATETIME DEFAULT NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_obs_pair (marco_a,marco_b),
 INDEX idx_obs_dup_status (status),
 CONSTRAINT fk_dup_obs_a FOREIGN KEY (marco_a) REFERENCES marcos(id) ON DELETE CASCADE,
 CONSTRAINT fk_dup_obs_b FOREIGN KEY (marco_b) REFERENCES marcos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
