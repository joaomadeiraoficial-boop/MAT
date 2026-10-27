# MAT — Roteiro de desenvolvimento, pesquisa e versões Git

**Projeto:** MAT — Modelagem da Aceleração Tecnológica e Índice Têmporis  
**Data do planejamento:** 26/10/2026  
**Natureza:** plano proposto; versões e commits abaixo ainda **não representam trabalhos executados**.  
**Repositório informado anteriormente:** https://github.com/joaomadeiraoficial-boop/MAT  

> **ATUALIZAÇÃO v6.2 (integração local):** foi criada uma nova base *MAT Observatório v6.2 Integrada* com visual autoral do Observatório, laboratório de triagem, sugestões de pontuação, fontes vinculadas, detecção de duplicatas, auditoria e ferramentas de migração para staging. Este avanço representa implementação de partes das etapas 6.1, 6.2, 6.5 e 6.6, **não a conclusão formal delas**. Testes de MySQL local, bibliografia, calibração matemática e validação ainda estão pendentes. O próximo commit deve validar a migração no XAMPP sem tocar no banco antigo. Detalhes em `docs/PARIDADE_E_MIGRACAO.md`.

## 1. O que preservar e o que corrigir

- **Preservar** o MAT existente (PHP 8, MySQL/MariaDB no XAMPP, interface, cadastro, busca, filtros e exportações).
- **Preservar** a hipótese de pesquisa, a Têmporis e sua arquitetura conceitual: marcos -> pontuação -> acúmulo -> índice.
- **Não substituir** a Têmporis pelo módulo de previsão. A previsão será um módulo separado, testado contra uma referência simples.
- **Corrigir antes de calcular:** conflito entre escalas do banco antigo (impacto e confiabilidade 1 a 5) e apresentação (outras escalas), falta de fator fundacional, regra de normalização `K_base` e versões de fórmula.
- **Distinguir fatos:** o relatório de análise da v5.1 informa Têmporis ainda não implementada no código. Há referência a atualização posterior v6 no Git; somente uma auditoria do repositório atual permitirá confirmar o que já foi corrigido.

### Duas famílias diferentes de autores

**Fonte de cronologia:** Bryan H. Bunch e Alexander Hellemans, *The History of Science and Technology* (2004, Houghton Mifflin). Catálogo da edição: https://openlibrary.org/books/OL23273051M/The_history_of_science_and_technology ; descrição editorial: https://books.google.com/books/about/The_History_of_Science_and_Technology.html?id=MlQ7NK9dw7IC . Fontes bibliográficas descrevem **mais de 7.000 entradas**, não foi confirmado aqui um banco oficial disponível em CSV/SQL nem o número exato de 8.583 registros. A obra cobre até 2003. **Nem toda entrada científica é automaticamente um marco tecnológico**.

**Fundamentação/contraponto:** Theodore Modis (2002), análise temporal de marcos; Jonathan Huebner (2005), questionamento da aceleração contínua. Esses pesquisadores não devem ser confundidos com os autores da cronologia acima.

**Importante:** extração do livro exige acesso legítimo ao conteúdo e avaliação das condições de uso. Não publicar no Git o texto integral da obra ou uma reprodução sistemática sem autorização; versionar código, esquema, referências, registros próprios/derivados permitidos e estatísticas agregadas.

## 2. Visão geral de versões propostas

| Versão | Objetivo | Produto verificável | Situação |
|---|---|---|---|
| v6.1.0 | Auditar repositório, fórmulas e banco; criar backup | Inventário e testes de funcionamento | A fazer |
| v6.2.0 | Estruturar proveniência e importação | Migração SQL + pipeline de importação em staging | A fazer |
| v6.3.0 | Extrair amostra da cronologia de Bunch/Hellemans | Lote-piloto conferido com páginas, datas e categorias | A fazer |
| v6.4.0 | Ampliar, limpar e validar base | Lote de dados rastreável, sem duplicatas óbvias | A fazer |
| v6.5.0 | Aplicar regras de seleção/pontuação | Rubrica, revisão e histórico de decisões | A fazer |
| v6.6.0 | Implementar Têmporis integralmente | Pᵢ, K(t), T(t) calculados e testados no painel | A fazer |
| v6.7.0 | Medir histórico e comparar modelos | Taxas temporais, linear/exponencial/logístico, erros e resíduos | A fazer |
| v6.8.0 | Fazer validação científica | Testes retrospectivos, sensibilidade, incerteza e comparações externas | A fazer |
| v6.9.0 | Construir modelo de previsão complementar | Previsões contra baselines, sem vazamento temporal | A fazer — extensão |
| v6.10.0 | Automatizar atualizações e exibir resultados | Importação agendada com aprovação; painel e logs | A fazer — extensão |
| v7.0.0 | Publicar entrega acadêmica reproduzível | Código estável, relatório, manual, testes e release Git | A fazer |

> **Regra de escopo:** TCC defendível até v6.8.0 + documentação final. Previsão automatizada e atualização autônoma são diferenciais se houver tempo e dados adequados. Em caso de dificuldade de extração, use uma amostra selecionada de forma documentada em vez de prometer a importação de todo o livro.

## 3. Etapas e commits, com critério de conclusão

### v6.1.0 — Auditoria e proteção do trabalho atual

**Commit sugerido:** `chore: documenta estado atual do MAT e prepara backups`  
**Arquivos previstos:** `docs/ESTADO_ATUAL.md`, `docs/METODOLOGIA_ATUAL.md`, `docs/ROADMAP.md`, `.gitignore`, `tests/`.

Tarefas:
1. Verificar efetivamente a branch `main`, último commit e funcionalidades do código atualizado; não presumir que a análise da v5.1 descreve a versão atual.
2. Registrar tabelas e colunas, telas, endpoints PHP, fórmulas e inconsistências entre artigo, apresentação e código.
3. Fazer backup local do banco e testar restauração em ambiente de teste.
4. Impedir que senhas, arquivos de configuração privados, livros e dumps completos sejam publicados no Git.

**Aceite:** sistema abre no XAMPP; relatório de estado atual; backup restaurável; Git limpo.

### v6.2.0 — Banco para dados científicos rastreáveis

**Commit sugerido:** `feat(db): cria esquema de fontes, eventos importados e revisoes`  
**Arquivos:** `database/migrations/`, `docs/DICIONARIO_DADOS.md`, `tests/test_db_*`.

Criar/adaptar tabelas equivalentes a: `fontes`, `lotes_importacao`, `eventos_brutos`, `marcos_candidatos`, `marco_fontes`, `avaliacoes`, `historico_revisoes`, `versoes_metodologia`, `execucoes_modelo`.

Guardar para cada registro: identificador de origem, obra/edição, página, categoria original, ano ou intervalo (incluindo a.C.), precisão da data, descrição resumida própria, tipo de fonte e situação de revisão. O **original importado** deve ser preservado separadamente da **versão validada** para impedir alterações silenciosas.

**Aceite:** migrations repetíveis, importação sem destruir marcos atuais, rastreamento do registro até sua fonte.

### v6.3.0 — Prova de extração de Bunch e Hellemans

**Commit sugerido:** `feat(etl): implementa extracao piloto da cronologia historica`  
**Arquivos:** `etl/extrair.py`, `etl/normalizar.py`, `etl/validar.py`, `docs/FONTE_BUNCH_HELLEMANS.md`, `tests/test_etl.py`.

1. Identificar a edição e o formato **legitimamente acessível** (e-book com texto selecionável, PDF autorizado, exemplar físico para transcrição de amostra etc.).
2. Testar extração de **50–100 entradas distribuídas entre diferentes épocas**, anotando página/capítulo.
3. Separar ano, evento, descrição, categoria e eventuais intervalos; marcar data incerta e ambiguidades.
4. Conferir manualmente uma amostra do resultado e quantificar a taxa de erros de extração.

**Aceite:** amostra rastreável e um relatório objetivo de qualidade. **Não assumir** que há banco oficial digital ou que OCR é sempre necessário.

### v6.4.0 — Base histórica validada

**Commit sugerido:** `feat(data): adiciona limpeza, deduplicacao e auditoria de marcos`.

- Importar outros lotes somente após validar o piloto, com checkpoint e capacidade de reprocessamento.
- Eliminar duplicações evidentes sem descartar ocorrências historicamente distintas.
- Separar descoberta científica, tecnologia, adoção em escala e evento social; explicitar quais entram no estudo.
- Estratificar por período e área e documentar **viés de sobrevivência documental** e de cobertura recente.
- Criar relatório com total por etapa: coletados, duplicados, excluídos, pendentes, aprovados.

**Aceite:** consultas reproduzíveis no banco, quadro quantitativo de triagem e fontes associadas.

### v6.5.0 — Critérios científicos de seleção e pesos

**Commit sugerido:** `feat(method): documenta rubrica objetiva de marcos e pontuacao`.

- Definir antecipadamente critérios de inclusão/exclusão e exemplos positivos/negativos.
- Fixar escalas coerentes de impacto `I`, confiabilidade `C` e fator fundacional `F`; converter ou migrar campos antigos com histórico da transformação.
- Formular critérios observáveis e registrar a **justificativa individual** de cada pontuação.
- Usar idealmente um segundo avaliador em amostra cega e relatar taxa de concordância/divergência.
- Só usar eventos aprovados no cálculo principal; manter rejeitados e pendentes para auditoria.

**Aceite:** duas pessoas podem aplicar a mesma rubrica e comparar decisões; nenhuma nota é alterada sem histórico.

### v6.6.0 — Motor da Têmporis (núcleo preservado)

**Commit sugerido:** `feat(temporis): implementa indice ponderado e testes de calculo`.

Registrar uma **versão oficial de fórmula**, por exemplo a que aparece na apresentação:

```text
P_i = (I_i² × C_i/100) × F_i
K(t) = soma(P_i para t_i <= t)
T(t) = 100 × (K(t)/K_base)^0,65
```

Escolher e justificar `K_base` sem empregar dados futuros indevidamente. Fixar método para datas incertas e períodos históricos. Separar `indice_temporis` de `modelo_preditivo`. Criar testes para casos simples, extremos, valores faltantes e mudanças de fórmula.

**Aceite:** resultado reprodutível via banco e interface; testes automáticos conferem com cálculos manuais.

### v6.7.0 — Modelagem temporal

**Commit sugerido:** `feat(models): compara tendencias linear exponencial e logistica`.

- Medir marcos por intervalos de mesma duração (por década/século, conforme período) e diferenças entre ocorrências, registrando limites para períodos muito antigos.
- Analisar tanto contagens e pontuações quanto a taxa de mudança do índice. Índice acumulado crescente **não demonstra aceleração por si só**.
- Implementar ajustes linear, exponencial e logístico e análise de resíduos.
- Relatar R², RMSE, MAE e tamanho da amostra, sem escolher curva apenas pelo maior R².

**Aceite:** tabelas e gráficos reproduzíveis a partir de uma mesma versão do dataset.

### v6.8.0 — Validação retrospectiva e robustez

**Commit sugerido:** `test(science): adiciona backtesting e sensibilidade do indice`.

- Congelar cortes históricos, treinar apenas com o passado e testar em intervalos posteriores.
- Alterar ponderações da Têmporis sistematicamente, excluir grupos de marcos e verificar a estabilidade.
- Comparar com séries independentes adequadas (patentes, adoção, produção científica etc.), **sem tratar correlação como causalidade**.
- Relatar limitações e resultados negativos, incluindo erro por época e categoria.

**Aceite:** tabelas de erros, intervalos/sensibilidade e protocolo que outra pessoa consegue repetir.

### v6.9.0 — Estimador complementar inspirado em apuração parcial (extensão)

**Commit sugerido:** `feat(forecast): implementa estimador historico com dados parciais`.

- Iniciar com **indicador observável** e histórico estruturado; ocultar 5%, 10%, 20% etc. de um período conhecido.
- Comparar estimador ingênuo com histórico + correção estratificada + regularização.
- Repetir simulações com amostra aleatória e chegada enviesada; medir MAE/RMSE e cobertura dos intervalos.
- Fazer um teste separado para **previsão de períodos futuros**. Reconstruir dados ocultados não equivale a prever inovações inexistentes.
- Avaliar depois a possibilidade de usar variáveis da Têmporis como entradas; não reescrever sua fórmula.

**Aceite:** previsões armazenadas antes de revelar o valor verdadeiro, melhoria demonstrável sobre baseline em dados fora de treino.

### v6.10.0 — Automações e painel (extensão)

**Commit sugerido:** `feat(automation): agenda coleta e publica execucoes auditaveis`.

- Agendar novas coletas de fontes **licenciadas e estáveis**, com logs de erro e limites de requisição.
- Novas entradas passam por fila de revisão; não entram automaticamente no índice validado.
- Exibir versão do dataset, método, data de atualização, quantidade de pendências, modelos, resultados e intervalos de incerteza.
- Reprocessamento idempotente: rodar o mesmo lote duas vezes não gera duplicatas.

**Aceite:** atualização de teste é auditável e não altera indevidamente históricos consolidados.

### v7.0.0 — Entrega final do TCC

**Commit sugerido:** `docs(release): finaliza reprodutibilidade e demonstracao do MAT`.

Concluir artigo/TCC, referências, manual de instalação XAMPP, instruções de migração, dicionário de dados, protocolo de coleta, autorização/licenças, experimentos e resultados, limitações, capturas do software, testes e instruções para reprodução. Fazer demonstração completa em uma instalação limpa.

**Aceite:** terceiro consegue clonar o repositório, instalar dependências, importar base de exemplo **distribuível** e reproduzir ao menos os resultados principais.

## 4. Fluxo Git recomendado para cada versão

> Todos os nomes de tags a partir de `v6.1.0` são **propostos**. Primeiro verificar se já existem versões/commits com esses nomes.

```bash
# Antes de tudo, na pasta real do repositório (não na pasta do XAMPP por suposição):
git status
git branch --show-current
git log --oneline -5
git tag --list

# Exemplo de trabalho para a primeira etapa:
git switch main
git pull origin main
git switch -c feature/v6.1-auditoria

# Criar/alterar somente os arquivos dessa etapa, rodar testes e conferir diferenças:
git status
git diff
git add docs/ESTADO_ATUAL.md docs/ROADMAP.md .gitignore
git commit -m "chore: documenta estado atual do MAT e prepara backups"

# Quando aprovado/testado:
git switch main
git merge --no-ff feature/v6.1-auditoria
git tag -a v6.1.0 -m "Auditoria e baseline do MAT"
git push origin main
git push origin v6.1.0
```

**Atenção:** o comando `git add` acima supõe que os arquivos já existam. Não fazer `git add .` indiscriminadamente, nem versionar `.env`, credenciais, dumps particulares ou material editorial protegido. Se o Git remoto estiver configurado de outra forma, ajustar após auditar `git remote -v`.

## 5. Regra de decisão para não estourar o TCC

**Essencial:** versões 6.1 a 6.8 e documentação da 7.0; implementar corretamente e validar a Têmporis.  
**Extensão desejável:** 6.9 (modelo complementar de previsão).  
**Extensão posterior:** 6.10 (coleta/atualização mais autônoma).  
**Não negociar:** procedência dos registros, coerência das escalas e ausência de vazamento temporal.

## 6. Próxima ação concreta

Começar pela **v6.1.0**: comparar o estado real do repositório atual com o antigo relatório da v5.1, inventariar o esquema MySQL, efetuar backup e registrar pendências. Somente então iniciar a migração da v6.2.0.
