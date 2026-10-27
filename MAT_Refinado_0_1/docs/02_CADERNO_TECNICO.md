# MAT — Versão Refinada 0.1
## Caderno técnico-mestre e contrato de continuidade

**Documento normativo para o desenvolvimento e o TCC** · edição de 26/10/2026  
**Identidade científica:** MAT — Modelagem da Aceleração Tecnológica  
**Produto:** MAT · Observatório do Tempo  
**Ciclo:** Versão Refinada **0.1.0** (baseline herdado do Observatório 6.2.1 e do MAT v6 Têmporis Lab)  
**Roadmap resumido:** [`01_ROADMAP_GERAL.md`](01_ROADMAP_GERAL.md)

> **Regra de consulta:** quando existirem diferenças entre conversa, proposta, protótipo visual, README antigo e este documento, **não corrigir silenciosamente**. Consultar o código, documentar a divergência, decidir com o pesquisador e registrar a alteração. O caderno descreve tanto o estado conhecido quanto os requisitos que ainda não existem.

---

## 1. Questão científica, produto e limites

### 1.1 Questão e hipótese

**Pergunta central:** em que medida marcos tecnológicos documentados permitem quantificar transformações ao longo da história e testar, em vez de presumir, padrões de aceleração tecnológica?

**Hipótese testável:** alguns indicadores de transformação tecnológica podem exibir intensificação temporal; a magnitude e até a direção desse padrão dependem da fonte, cobertura histórica, unidade de análise, escala e critérios de seleção. O trabalho **não** toma aceleração exponencial contínua como fato garantido.

**Contribuições separadas:**

1. **Acervo computacional:** registros verificáveis, categorizados, datados e auditáveis, contendo origem e decisões de inclusão.
2. **Têmporis:** índice composto de desenvolvimento/transformação tecnológica construído a partir de pontuações de marcos e agregado no tempo.
3. **Modelagem:** comparação empírica de funções e taxas temporais, com erros, resíduos, sensibilidade e referências externas.
4. **Previsão:** módulo complementar *opcional* que tenta estimar indicadores futuros e mede sua acurácia fora da amostra; não substitui 1–3.

**Não objetivos da fase 0.1:** afirmar quando surgirá a próxima tecnologia; construir IA autônoma de decisão editorial; reproduzir integralmente obras protegidas; afirmar a validade do índice com apenas 17 marcos; substituir todo o sistema por uma interface estática.

### 1.2 Identidade e linguagem

Manter **MAT** como sigla de pesquisa e **Observatório do Tempo** como linguagem visual autoral: editorial, cartográfica, científica; base cromática escura verde-petróleo, superfícies de papel, detalhes de cobre, desenho de acervo histórico. **Versão Refinada 0.1** é o nome do ciclo atual de desenvolvimento, não novo modelo matemático. Melhorias de interface devem respeitar a identidade, acessibilidade e legibilidade. Recuperar funcionalidades antigas sem trazer de volta seu estilo.

### 1.3 Hierarquia do que sabemos

- **Base de aplicação conhecida:** arquivos locais do Observatório **v6.2.1** com PHP, MySQL, páginas de acervo, dossiês, laboratório, fontes, Têmporis, importador, exportações e testes de núcleo.
- **Legado referenciado:** ZIP do **MAT_v6_Temporis_Lab**, com PHP, importação inteligente, duplicatas, regras de escopo, validação, coletor, assistente de métricas e trilha de auditoria. Sua árvore contém cópias de partes do próprio projeto; analisar a raiz canônica e evitar reimportar diretórios duplicados.
- **Dados iniciais:** **17 candidatos documentais**, inseridos como `pendente`, sem pontuação e `incluir_temporis=0`; eles são um **piloto de engenharia e curadoria**, não amostra representativa para testar uma teoria universal.
- **Ambiente de execução:** XAMPP no Windows (PHP 8, MySQL/MariaDB, Apache); nenhuma operação feita aqui assegura que o MySQL local do pesquisador foi testado.
- **Verificações nesta preparação:** sintaxe PHP dos arquivos correntes e `tests/test_core.php` + `tests/test_science.php` passaram em ambiente sem `pdo_mysql`. **Não são testes end-to-end** e não demonstram gravação real em banco.

---

## 2. Invariantes — princípios que jamais devem desaparecer por refatoração

**R1 — Preservação funcional verificável:** qualquer funcionalidade existente no Lab antigo deve permanecer acessível ou possuir substituição equivalente demonstrável. Um botão com mesmo nome não constitui paridade. O desenvolvimento deverá cobrir entrada, transformação, persistência, validações, confirmação, resultado e auditoria. Recursos ausentes são **pendências**, não recursos preservados.

**R2 — Isolamento do legado:** repositório `main` antigo inalterado; versão refinada em branch própria. Exportar backup do MySQL antes de qualquer migração, testar restauração e nunca executar alterações destrutivas diretamente na origem.

**R3 — Dados brutos imutáveis:** nunca descartar a representação original de um evento. Normalizar para uma entidade separada ou com revisão histórica. Conservar fonte, lote, data de ingestão, intervalo temporal original, autor da decisão e transformação aplicada.

**R4 — Triagem humana:** busca automática, classificador, pré-codificação e agrupamento de duplicatas geram **sugestões**. Aprovação e inclusão no índice são decisões explícitas, registradas.

**R5 — Evidência e proveniência:** um marco pode ter várias fontes e cada fonte papel distinto: origem, confirmação, data, impacto, contexto. Idealmente guardar edição, autor, publicação, URL/DOI, página/trecho, tipo e data de acesso.

**R6 — Versionamento científico:** escalas, pesos, expoentes, referência normalizadora, regras de datas e elegibilidade precisam ter identificador de versão e justificativa. Alterar fórmula nunca reescreve silenciosamente cálculos anteriores.

**R7 — Separação dos resultados:** dados do piloto, scores experimentais, medições validadas e previsões devem ser identificados visualmente e nas exportações. Sem valor = nulo, jamais zero inventado.

**R8 — Reprodutibilidade:** mesma seleção de marcos, dados, parâmetros e versão de código devem reproduzir os mesmos números, com log de execução.

**R9 — Segurança mínima:** PDO e consultas parametrizadas; proteção CSRF em operações de escrita; autenticação/autorização; validação de uploads; escaping na renderização; segredos fora do Git; logs não expostos publicamente.

**R10 — Narrativa neutra quanto ao resultado:** resultado linear, logístico, estacionário ou em desaceleração é tão válido quanto exponencial, desde que cientificamente defendido.

---

## 3. Matriz de preservação do MAT antigo

**Legendas:** **presente/adaptado** = há módulos correspondentes no Observatório e é necessária prova de paridade E2E; **parcial** = só parte da experiência/regra existe; **pendente** = o recurso antigo não foi portado para a base refinada. Esta matriz é critério de produto, não relatório de testes no MySQL real.

| ID | Recurso necessário do Lab v6 | Base refinada v0.1 | Referência no código anterior / destino ou providência | Teste de aceitação obrigatório |
|---|---|---|---|---|
| LF01 | Dashboard, indicadores e cobertura | Presente/adaptado | Antigo `dashboard.php`; novo `views/observatorio.php`, `views/laboratorio.php` | Totais batem com queries e filtros |
| LF02 | CRUD de marcos e campos completos | Presente/adaptado | Antigo `marco.php`, `marcos.php`; novo `views/editar.php`, `actions.php` | Criar, editar, listar, excluir e auditar |
| LF03 | Busca, filtros e paginação | Presente/adaptado | Novo `views/acervo.php`, `app/repository.php` | Consulta não perde registros nem duplica páginas |
| LF04 | Linha histórica, atlas, panfletos e dossiês | Presente/adaptado | Novas `views/linha.php`, `atlas.php`, `galeria.php`, `marco.php` | Navegação coerente com o registro original |
| LF05 | Exportação CSV, JSON e SQL | Presente/adaptado | Novo `export.php` | Codificação, integridade e escaping, sem segredos |
| LF06 | Importador CSV, TXT e TSV, separador automático e mapeamento visual | **Parcial** | Antigo `src/ImportService.php`, `importar.php`; novo `views/importar.php` aceita CSV | Carregar três formatos, prévia, mapeamento, bloquear importação errada |
| LF07 | Preservação de arquivo, linha e conteúdo bruto | Presente/adaptado | Novo `import_itens`, JSON bruto | Original recuperável após normalização |
| LF08 | Fila de revisão e classificação de escopo | Presente/adaptado | Novo `views/triagem.php`, `app/ScopeClassifier.php` | Sugestão não aprova nem insere na Têmporis |
| LF09 | Regras/dicionário de escopo editáveis | **Pendente** | Antigo `regras_escopo.php`, `ScopeClassifier.php` com regras | Editar regra, versionar e reprocessar sem apagar decisões |
| LF10 | Ações e aprovação de escopo em lote | **Pendente** | Antigo `escopo_lote.php`, APIs do legado | Prévia, seleção, confirmação, log e desfazer seguro |
| LF11 | Detecção, recálculo e revisão de duplicatas | Parcial | Novo `app/DuplicateDetector.php`, `views/duplicatas.php` | Casos acrônimo/ano distintos, revisões persistentes |
| LF12 | Mesclagem auditável de duplicatas | **Pendente** | Antigo `duplicatas.php` e `api/duplicate_action.php` | Mesclar sem perder fontes, raw ou histórico; rollback |
| LF13 | Catálogo de fontes e vínculos por papel | Presente/adaptado | Novo `views/fontes.php`, tabelas `fontes`, `marco_fontes` | Vários documentos por marco, origem comprovada |
| LF14 | Trechos/página de evidência detalhados | Parcial | Antigo `fontes.php`, `MetricSuggester.php` | Campos preservados ao importar/exportar |
| LF15 | Rubrica de 4 dimensões de impacto + 3 fundacionais | Parcial | Antigo `app/Methodology.php`; novo `app/Methodology.php` | Cada critério/evidência documentado; mesma pontuação esperada |
| LF16 | Configurar `β` e realizar teste de sensibilidade | **Pendente/parcial** | Antigo `validacao.php`, `app/Temporis.php` | Variar parâmetros sem alterar o dataset; relatório de efeitos |
| LF17 | Sugestões de 7 métricas e C documental em lote | Parcial | Antigo `assistencia.php`, `api/batch_suggest_metrics.php`; novo `app/MetricSuggester.php` | Modo estrito, contradição, revisão, histórico de runs |
| LF18 | Quality gate para aprovação e entrada no índice | Presente/adaptado | Novo `app/science.php`, `app/temporis.php` | Registros sem fonte/escopo/revisão/pesos nunca pontuam |
| LF19 | Coletor Wikidata com prévia, filtros, limite e exclusão de lote | **Pendente** | Antigo `app/ExternalCollector.php`, `coletor.php` | Prévia obrigatória, não aprovar sem revisão, exclusão segura |
| LF20 | Trilha de auditoria e revisão | Presente/adaptado | Novo `views/auditoria.php`, `revisoes` | Registrar autor, data, ação, antes/depois |
| LF21 | Alterar ano-base/α experimentalmente e série histórica | Parcial | Antigo `temporis.php`, `validacao.php`; novo `views/temporis.php` | Cenário não modifica configuração oficial |
| LF22 | Tema claro/escuro e navegação acessível | **Parcial** | Novo design autoral preservado; tema alternativo do Lab ausente | Preferência persistente, contraste, teclado, menu móvel |
| LF23 | Exclusão segura de lotes importados | **Pendente** | Antigo `api/delete_batch.php` | Prévia, dupla confirmação, auditar e impedir exclusão cruzada |
| LF24 | Autenticação, CSRF e segurança de entrada | Presente/adaptado; revisar | Novo `actions.php`, `cli/criar_admin.php` | Bloquear mutações não autorizadas; testar CSRF e uploads |
| LF25 | Importação conservadora do MySQL antigo | Parcial | Novo `cli/importar_lab_v6.php` só importa `marcos` para staging | Migrar fontes e dados relacionados sem perder histórico |

**Regra de aceite M1:** nenhuma linha ficará «pendente» ou «parcial» sem ticket, plano, teste e decisão explícita. Arestas dependentes (fontes/pareceres/score/duplicatas) fazem parte do contrato. **Não substituir a metodologia antiga por um cálculo diferente por conveniência.**

---

## 4. Fontes, população e qualidade dos dados

### 4.1 Fontes com papéis distintos

- **Bunch e Hellemans**, *The History of Science and Technology* (edição de 2004): candidato a fonte bibliográfica de cronologias extensas. Sua extração depende de acesso legítimo e termos de uso. Não foi demonstrada a existência de um SQL/CSV oficial completo. Ciência registrada na obra ≠ evento tecnológico automaticamente elegível.
- **Theodore Modis (2002)**: referência metodológica sobre marcos, intervalos e comparação de padrões; deve ser discutido o viés das listas de eventos.
- **Jonathan Huebner (2005)**: contraponto crítico à ideia de aceleração ilimitada. O TCC não pode selecionar apenas autores favoráveis à hipótese.
- **Fontes institucionais por acontecimento:** base piloto de 17 registros com links próprios; cada ligação precisa de conferência humana quanto ao que efetivamente suporta.
- **Indicadores externos:** séries de patentes, adoção, pesquisa, capacidade ou produtividade, se comparáveis e com fontes auditáveis, para validação convergente. Não tratar total de publicações como sinônimo de inovação.

### 4.2 Estado e definição operacional do marco

Campos mínimos: identificador estável, título, descrição própria, ano central opcional, intervalo cronológico, precisão de data, domínio tecnológico, categoria de evento (descoberta/invenção/demonstração/adoção), proveniência, revisão, elegibilidade, decisões de duplicata, conjunto de critérios, versão metodológica e status de aprovação.

Fluxo obrigatório: `descoberta de candidato → dado bruto → normalização → deduplicação sugerida → fontes → parecer de escopo → avaliação da rubrica → aprovação humana → inclusão explícita na Têmporis → snapshot de cálculo`.

Estados recomendados e distinguíveis: `importado`, `pendente`, `em_revisao`, `aprovado`, `rejeitado`, `duplicado`; respeitar o esquema real antes de aplicar migrações. Não tratar registro inserido por script como cientificamente homologado.

### 4.3 Amostra de 17 marcos

Os **17 candidatos** cobrem aproximadamente 3300 a.C.–2012, combinando setores e épocas diferentes. Na versão 0.1 não possuem notas oficiais, estão `pendente` e `incluir_temporis=0`. Importação SQL: `database/base_piloto_17_marcos.sql`; fontes e descrição: `docs/BASE_CIENTIFICA_PILOTO.md`.

**Pode ser usada para:** validar schema, fontes, cadastro, fluxo de aprovação, cobertura de períodos, exportação, cálculos unitários sobre dados fictícios e interfaces. **Não pode ser usada para:** estimar a aceleração de toda a história, construir intervalo estatístico representativo ou anunciar predição validada.

### 4.4 Evitar vieses

Definir população-alvo, método de amostragem e janelas cronológicas antes da comparação. Normalizar quantidades por tempo observado; testar coberturas entre eras, setores e geografias; separar sobrevivência documental de frequência de inovação; considerar vínculos de dependência e dupla contagem; usar análise de sensibilidade ao recorte histórico, à política de inclusão e às fontes. Não assumir independência estatística entre marcos correlacionados.

---

## 5. Contrato matemático da Têmporis

### 5.1 Fórmulas basilares preservadas

Para cada marco elegível `i`:

\[
P_i = I_i^{\beta}\left(\frac{C_i}{100}\right)F_i,\qquad \beta_{\mathrm{base}} = 2.
\]

\[
K(t)=\sum_{i:\,t_i\le t} P_i
\]

\[
T(t)=100\left(\frac{K(t)}{K_{\mathrm{base}}}\right)^\alpha,\qquad \alpha_{\mathrm{base}}=0{,}65.
\]

**Continuidade:** a fórmula original `Pᵢ=(Iᵢ² × Cᵢ/100) × Fᵢ` é exatamente o caso β=2. `K_base` é fixo e positivo e sua seleção precisa ser documentada sem vazamento temporal. `T(t)` no ano-base assume 100 pela construção, não por evidência empírica independente.

### 5.2 Operacionalização do Lab legado (não descartável)

**Impacto** (4 critérios com evidência própria, cada qual 0–3): difusão, magnitude da capacidade criada/ampliada, alcance e persistência histórica. Configuração original de pesos iguais:

\[
I = 1 + 9\frac{d+m+a+p}{12}
\]

**Fundacional** (3 critérios com evidência própria, cada qual 0–3): pervasividade, aprimoramento potencial e complementaridade inovadora:

\[
F=1+0{,}5\frac{v+r+c}{9}
\]

**Confiabilidade `C`** é pontuação documentada em 0–100 de suporte documental, com justificativa, **não** probabilidade estatística de que o marco seja verdadeiro. A pré-codificação automática sugere notas, mas não fornece evidência suficiente para aprovação sem julgamento humano.

**Parâmetros a manter visíveis:** β, α, normalizador `K_base`, pesos dos 7 critérios, versão da rubrica e regra de ano/intervalo. O Observatório atual ainda usa conversão experimental de notas legadas `I(1–5)×2` e `C(1–5)×20` em parte do motor: isso **não** equivale à rubrica de 7 critérios e precisará de migração/reconciliação versionada, jamais fusão silenciosa.

### 5.3 Gate de elegibilidade

**Só calcular `Pᵢ` para marco com:** status aprovado + fonte vinculada + escopo revisado como elegível + evidência suficiente + pontuação completa + `incluir_temporis=1`. O valor `Pᵢ` não deve ser interpretado em registros sem essa combinação, ainda que a interface permita exibir um cenário hipotético identificado como tal.

Quando `K_base≤0` ou não existir conjunto elegível, mostrar **índice indisponível / sem amostra validada**, não inventar 0 nem 100. Repetições (duplicatas aprovadas por engano), datas incertas e atualização de fonte exigem recálculo rastreável.

### 5.4 Precauções estatísticas

- `K(t)` acumula valores por definição; uma curva crescente não é prova de aceleração. Derivar taxas por janelas comparáveis e comparar modelos de crescimento.
- `α` e `β` devem ser testados por sensibilidade; preferir não escolher parâmetros apenas porque ampliam o efeito desejado.
- Tratar `ano=-3300` como convenção computacional para 3300 a.C.; converter cuidadosamente intervalos históricos ao calcular distâncias (a cronologia convencional não possui ano zero).
- Medir influência de cada fonte/marco individual, remoção de outliers, reavaliação de pesos, categorias e períodos.
- Executar avaliação interavaliadores em uma amostra para investigar subjetividade e discordância da rubrica.

---

## 6. Modelagem temporal, validação e previsão

### 6.1 Dados de entrada e variáveis observáveis

Calcular contagem e soma ponderada de marcos por janela histórica, densidade por ano/década/século, intervalos entre eventos e crescimento de indicadores externos. Séries históricas com longos vazios ou mudanças de cobertura exigem análise do denominador e limites da comparação.

### 6.2 Modelos a comparar

Modelos linear, exponencial e logístico — cada um com domínio de validade, parâmetros estimados e eventual saturação. Registrar R², RMSE, MAE, resíduos e critérios de seleção pertinentes. Um ótimo ajuste na amostra **não** significa boa previsão futura. Comparar também com baseline ingênuo e desempenho fora da amostra.

### 6.3 Desenho de validação

1. Congelar dataset, método, pesos e recorte de treinamento.
2. Ajustar apenas com dados anteriores ao corte temporal.
3. Prever períodos posteriores ainda ocultos e comparar com o observado; repetir janelas (backtesting/rolling-origin).
4. Reportar distribuição de erros, vieses e intervalos de incerteza, não só um R² favorável.
5. Testar robustez a pesos, `α`, `β`, datas, seleção de marcos, categorias, cobertura e referência externa.
6. Especificar falhas, dados insuficientes e situações em que modelos simples são superiores.

**Inspiração eleitoral:** o princípio da referência histórica e da estimativa da mudança pode orientar experimentos controlados em indicadores suficientemente densos, mas não autoriza afirmar que 17 marcos permitem reproduzir a precisão de projeções eleitorais. Previsão de pontos faltantes de uma série é diferente de prever invenções futuras.

### 6.4 Módulo MAT-Proj (opcional e isolado)

Quando existir série histórica adequada, implementar uma API/serviço de ajuste em Python (Pandas, NumPy, SciPy, scikit-learn e/ou statsmodels) sem mudar o significado da Têmporis. Versionar dataset, parâmetros, horizontes e métricas. Nunca treinar nos valores ocultos que serão usados para avaliação. Projeções são cenários condicionais, não fatos.

---

## 7. Contrato de arquitetura e persistência

### 7.1 Baseline (arquivo real)

```text
MAT_Refinado_0_1/
├── app/                    # domínio, conexão, metodologia, Têmporis
├── views/                  # identidade editorial e telas
├── assets/                 # CSS/JS/insígnia
├── api/                    # endpoints de consulta
├── cli/                    # criação de administrador e importadores
├── database/               # schema v6.2 + SQL piloto 17
├── docs/                   # roadmap, este caderno, dicionário e histórico
├── sample/                 # dados ilustrativos ou ainda não homologados
├── tests/                  # testes PHP unitários sem banco
├── uploads/                # nunca publicar conteúdo de usuário
├── index.php, actions.php, export.php
├── config.example.php      # commitável (sem senha real)
├── config.local.php        # PRIVADO: não versionar
└── VERSION                 # ciclo de organização 0.1.0
```

### 7.2 MySQL

Banco independente: `mat_observatorio`. Schema atualmente possui: `usuarios`, `fontes`, `import_lotes`, `marcos`, `import_itens`, `revisoes`, `marco_fontes`, `duplicate_candidates`. Conferir DDL em `database/schema.sql` antes de criar migrations.

**Nunca** reexecutar schema inicial como upgrade indiscriminado. Para instalação nova usar `database/schema.sql`; instalações 6.1 existentes exigem procedimentos controlados (`database/update_v6_2.sql`) após backup e verificação de versão. `database/base_piloto_17_marcos.sql` é carga adicional opcional e **não** altera o esquema. Registros do Lab antigo vão para staging via `cli/importar_lab_v6.php`, sem escrita na origem.

**Migração futura:** preservar chaves e relacionamentos de `marcos_raw`, fontes/trechos, revisões de escopo, pontuações, sugestões, versões de metodologia, lotes, duplicatas e auditoria. Migração já fornecida é **parcial**: marcos vão para `import_itens` com JSON bruto; não afirmar que importou o grafo relacional completo. Priorizar idempotência, log de conflitos e rollback.

### 7.3 Segurança e publicação

Nunca subir credenciais, SQL dumps privados, dados pessoais, uploads de usuários, textos extensos extraídos de livros, `.git` internos dos ZIPs nem arquivos que repliquem obra protegida sem autorização. Usar `.gitignore`; conferir `git status` antes do commit. Manter aplicação local até auditoria de autorização, uploads, CSRF e exposição de dados. GitHub só recebe exemplos permitidos e código.

---

## 8. Testes, aceite e qualidade

**Executados nesta preparação (ambiente sem PDO MySQL):**

```powershell
php tests/test_core.php
php tests/test_science.php
```

Ambos passaram; também houve verificação de sintaxe PHP. **Isso não significa que importação, CRUD, autenticação e persistência foram validados em XAMPP**.

**Testes mínimos obrigatórios para continuidade:**

| Camada | Como verificar | Condição de aceite |
|---|---|---|
| Código básico | `php -l` e testes de núcleo | Sem erro de sintaxe/regressão nas fórmulas |
| Banco | Instalar esquema e fazer transação de teste | Persistência correta e reversível; backups testados |
| Acervo | CRUD, busca, filtros, exportação | Dados e fontes preservados; sem XSS/SQL injection |
| Importação | CSV/TXT/TSV, mapeamento e lotes | Bruto preservado, prévia, dedup, confirmação e log |
| Escopo | Casos elegível, dúvida, fora do escopo | Classificador sugere; só humano decide |
| Métricas | 7 critérios, evidências, C e β | Fórmulas reproduzíveis, campos ausentes bloqueados |
| Têmporis | `Pᵢ`, `K(t)`, `T(t)` e filtros | Somente aprovados incluídos; `K_base` documentado |
| Duplicatas | Sinais por nome, acrônimo e data | Mescla só com confirmação e mantém proveniência |
| Automação | Preview, limites, erro e cancelamento | Nunca inserir/avaliar dados silenciosamente |
| Segurança | Sessões, CSRF, upload e autorização | Usuário não autenticado não modifica dados |
| Modelos | Conjunto temporal oculto e baseline | Sem vazamento; desempenho quantificado e negativo relatado |

**Definition of Done — obrigatório:** cada commit que modifica regra científica precisa trazer teste, explicação da fórmula/regra, efeito nos dados, e nota de migração. Cada commit que remove ou troca fluxo do Lab antigo deve atualizar a matriz LFxx e registrar como foi testada a equivalência.

---

## 9. Git, versões e política de conservação

**Repositório antigo:** `https://github.com/joaomadeiraoficial-boop/MAT` (endereço encontrado no `.git/config` incluído no ZIP do Lab; conferir no computador antes do push). A branch `main` é referência congelada durante a adoção do novo ciclo; **nunca usar `git push --force` ou substituir a árvore antiga sem revisão**.

**Branch sugerida:** `feature/mat-refinado-0-1` (ramo isolado criado a partir da versão sincronizada de `main`, após conferir alterações locais). **Diretório novo:** `MAT_Refinado_0_1/` dentro do repositório. Não copiar nenhuma pasta `.git` interna.

**Commits iniciais:**

1. `docs: define escopo e roadmap do MAT Refinado 0.1`
2. `chore: adiciona baseline preservada do Observatorio ao ciclo refinado`
3. Após XAMPP: `test: registra verificacao local do banco e fluxos criticos`

A tag `refinado-v0.1.0` só será aplicada depois de revisar a branch e concluir o smoke test no XAMPP. Manter histórico de mudanças e commits pequenos. Não fazer merge em `main` enquanto a matriz funcional possuir pendências relevantes.

---

## 10. Estado e retomada — LEITURA OBRIGATÓRIA NA PRÓXIMA CONVERSA

**Última decisão registrada:** criar o ciclo MAT — Versão Refinada 0.1, manter a identidade Observatório, adotar a base v6.2.1 com 17 marcos candidatos e **preservar toda capacidade do Têmporis Lab v6 por meio de testes de paridade**. Não reiniciar a pesquisa nem reformular suas equações sem justificativa científica.

**Arquivos-fonte conhecidos e papéis:**

- `MAT_Refinado_0_1/` — base de desenvolvimento nova e visualmente estável.
- `docs/01_ROADMAP_GERAL.md` — etapas gerais e entregas.
- `docs/02_CADERNO_TECNICO.md` — contratos científicos, arquitetura, riscos, paridade e procedimentos.
- `docs/historico/` — documentação anterior preservada para auditoria.
- Código do Lab antigo: branch `main` e o ZIP recebido do usuário; não transplantar `.git` do ZIP para a nova árvore.

**Já foi verificado:** testes PHP de núcleo e sintaxe; estrutura de arquivos. **Não foi verificado:** banco MySQL real no Windows, push/branch remoto, completude da migração, validação do índice em dados reais.

**Primeira ação ao voltar:** (a) conferir `git branch --show-current` e `git status`; (b) conferir se a documentação foi versionada; (c) instalar o baseline no XAMPP, executar os testes e anotar inconsistências; (d) iniciar **M1 — Paridade funcional** pelo importador inteligente, regras/lotes e rubrica. Não avançar ao modelo preditivo antes de M2–M6.

**Registro de decisões a preencher sempre:**

| Data | ID / assunto | Decisão | Evidência / teste | Commit |
|---|---|---|---|---|
| 2026-10-26 | D01 — nome e escopo | MAT Refinado 0.1 mantém marca Observatório, Têmporis e funcionalidades antigas como contrato de paridade | Inventário local de arquivos do Lab e Observatório | Ainda não publicado |
| 2026-10-26 | D02 — base científica | 17 candidatos com fontes iniciais; manter pendentes de revisão, sem notas inventadas | `database/base_piloto_17_marcos.sql` e documentação | Ainda não publicado |
| 2026-10-26 | D03 — previsões | Diferenciar índice, comparação de curvas e modelo preditivo opcional | Plano metodológico, sem resultados alegados | Ainda não publicado |

**Como pedir ajuda futuramente:** “Continue o MAT — Versão Refinada 0.1. Leia primeiro `docs/02_CADERNO_TECNICO.md` e `docs/01_ROADMAP_GERAL.md`; preserve todas as funções do Lab v6 e o design do Observatório; estamos na etapa M__ e no commit __. Avalie primeiro o código real e os testes.”

---

## 11. Referências bibliográficas estruturantes (a conferir na versão final do TCC)

- Modis, Theodore (2002). *Forecasting the Growth of Complexity and Change.* Referência usada como precedente para analisar marcos e intervalos; revisar bibliografia e forma de citação conforme publicação consultada.
- Huebner, Jonathan (2005). *A Possible Declining Trend for Worldwide Innovation.* Contraponto à aceleração ilimitada.
- Bunch, Bryan H.; Hellemans, Alexander (2004). *The History of Science and Technology.* Fonte de consulta cronológica candidata, sujeita a disponibilidade e licenças.
- Bresnahan, Timothy F.; Trajtenberg, Manuel (1995). *General Purpose Technologies: ‘Engines of Growth’?* Journal of Econometrics, 65(1), 83–108.
- OECD/Eurostat (2018). *Oslo Manual 2018*.
- OECD/JRC (2008). *Handbook on Constructing Composite Indicators*.

> As fontes históricas e metodológicas devem ser citadas conforme sua efetiva utilização. Não confundir bibliografia planejada com referências já analisadas integralmente.
