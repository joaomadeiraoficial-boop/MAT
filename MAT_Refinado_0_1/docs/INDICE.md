# Índice da documentação — MAT Refinado

Este documento é o **ponto de entrada único** da documentação do projeto (Etapa 1: organização e fluxo oficial).

## 1. Visão geral e referência técnica

| Documento | Conteúdo |
|---|---|
| [`../README.md`](../README.md) | Apresentação do projeto, estado da versão e instalação local (XAMPP) |
| [`01_ROADMAP_GERAL.md`](01_ROADMAP_GERAL.md) | Visão geral e marcos do ciclo |
| [`02_CADERNO_TECNICO.md`](02_CADERNO_TECNICO.md) | Caderno técnico-mestre e contrato de continuidade |
| [`PARIDADE_E_MIGRACAO.md`](PARIDADE_E_MIGRACAO.md) | Situação em relação à base anterior (Lab v6.x) e paridade/migração |
| [`DICIONARIO_DADOS.md`](DICIONARIO_DADOS.md) | Dicionário de dados |
| [`ESTADO_ATUAL.md`](ESTADO_ATUAL.md) | Estado atual registrado do projeto |
| [`BASE_CIENTIFICA_PILOTO.md`](BASE_CIENTIFICA_PILOTO.md) | Base piloto documental de 17 marcos (revisão, não validação) |
| [`DIRECAO_DE_ARTE.md`](DIRECAO_DE_ARTE.md) | Direção visual |
| [`INTEGRACAO_MAT_PROJ_NAVEGACAO.md`](INTEGRACAO_MAT_PROJ_NAVEGACAO.md) / [`03_MAT_PROJ_ETAPA_01.md`](03_MAT_PROJ_ETAPA_01.md) | Integração e etapas do MAT-Proj |
| [`CHECKLIST_COMMIT.md`](CHECKLIST_COMMIT.md) | Checklist de commit |
| [`CORRECOES_0_1_1.md`](CORRECOES_0_1_1.md) | Correções da revisão 0.1.1 |
| [`historico/`](historico/) | Documentação histórica preservada |

## 2. Fluxo oficial (referência atual)

- **Aplicação:** `index.php` (MAT Refinado 0.1 / MAT Final integrado). Método atual em destaque: `index.php?page=temporis` (MAT Final); comparação de formulações em `index.php?page=metodo`.
- **Metodologia adotada (MAT Final):** pontuação por 11 rubricas → dimensões I, C, F → peso `w_i = (I_i/10)^2 · C_i · F_i` → acúmulo `K(t)=Σ w_i` → índice proporcional **`T_F(t)=100·K(t)/K(t0)`** (sem compressão), com `t0` configurável. Formulações Legada/Reformulada permanecem para comparação/consulta. Ver [`../views/metodo.php`](../views/metodo.php) e [`../app/TemporisFinal.php`](../app/TemporisFinal.php).
- **Bancos (configuração padrão em `config.example.php` / `config.local.php`):**
  - `mat_observatorio` (`db_name`): acervo/observatório principal.
  - `mat_temporis_curadoria` (`curadoria_db_name`): curadoria de trabalho (dossiês, 11 critérios, fontes, rodadas/log).
  - `mat_final_curadoria` (`temporis_final_db_name`): base histórica isolada do MAT Final (opcional/snapshot/importação).
- **Princípios:** curadoria humana e rastreabilidade de fontes; dados extraídos/provisórios não equivalem a validação; homologação é distinta de prévia/ensaio; o índice **não comprova por si só** aceleração tecnológica.

> Detalhes de instalação, esquemas e scripts: [`../database/`](../database/), guias em [`guias/`](guias/) e notas em [`notas/`](notas/).

## 3. Guias (instalação, operação e leituras iniciais)

Local: [`guias/`](guias/)

- **Leituras iniciais / contexto:** `LEIA_PRIMEIRO.md`, `LEIA_PRIMEIRO.txt`, `LEIA_PRIMEIRO_INSTALACAO.txt`, `LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md`, `LEIA_PRIMEIRO_MODO_SIMPLIFICADO.md`, `LEIA_PRIMEIRO_MESCLA_SALVAMENTO.md`, `LEIA_PRIMEIRO_AUDITORIA_AUTOMATICA.txt`, `LEIA_PRIMEIRO_TRIAGEM_ASSISTIDA_0_1_3.txt`, `LEIA_PRIMEIRO_ATUALIZACAO_02.txt`, `LEIA_PRIMEIRO_ATUALIZACAO_04.txt`
- **Instalação e integração:** `GUIA_XAMPP_0_1_2.md`, `COMO_INSTALAR_MAT_INTEGRADO.md`, `INSTALAR_INTERVALOS_E_ENSAIO.txt`, `INSTALAR_FILA_RAPIDA.txt`, `INSTALAR_FILA_RAPIDA_V2.txt`, `INSTALAR_ANALISE_DUPLICATAS_V3.txt`

> Observação: alguns guias são incrementais/históricos de pacotes específicos; em caso de dúvida, prevalecem o [`../README.md`](../README.md), este índice e as notas mais recentes.

## 4. Notas, correções e relatórios

Local: [`notas/`](notas/)

- **Correções/metodologia:** `CORRECAO_METODO_PRINCIPAL.md`, `ALTERACAO_BACKTESTING_SIMPLES.md`
- **Alterações e integração:** `ALTERACOES_E_TESTES.md`, `ALTERACOES_MAT_FINAL_INTEGRADO.md`
- **Qualidade, protocolo e testes:** `MELHORIAS_E_LIMITACOES_0_1_2.md`, `PROTOCOLO_CIENTIFICO_CURADORIA_0_1_2.md`, `RELATORIO_DE_TESTES_0_1_2.md`, `TESTES_MESCLAGEM.md`, `RELATORIO_ALTERACOES_MODO_SIMPLIFICADO.md`

## 5. Organização e etapas

- **Etapa 1 (concluída, base):** guias em `docs/guias/`, notas em `docs/notas/`, duplicações do `README.md` removidas e referências atualizadas.
- **Etapa 2 (em andamento):** mapeamento de bancos/esquemas, rotina de backup/restore e estratégia de migrações em [`notas/ETAPA2_BANCO_MIGRACOES.md`](notas/ETAPA2_BANCO_MIGRACOES.md); migrações em [`../database/migrations/`](../database/migrations/); verificações em [`../database/verificacao_consistencia_curadoria.sql`](../database/verificacao_consistencia_curadoria.sql) e [`../cli/verificar_consistencia_db.php`](../cli/verificar_consistencia_db.php).
- **Próximas etapas (plano):** ampliação de testes (Etapa 3), configuração/operação (Etapa 4) e apoio à curadoria (Etapa 5).
