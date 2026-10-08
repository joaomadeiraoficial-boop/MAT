# MAT v6 — Laboratório Têmporis

Protótipo local em **PHP 8 + MariaDB/MySQL**, pensado para transformar o MAT em um ambiente de pesquisa: importar bases grandes, preservar o dado bruto, normalizar, revisar, detectar duplicatas, vincular fontes, pontuar marcos e calcular a série histórica da Têmporis.

## Instalação no XAMPP

1. Copie a pasta `MAT_v6_Temporis_Lab` para `C:\xampp\htdocs\`.
2. Abra o phpMyAdmin e importe `database/schema.sql`.
3. Confirme em `config/database.php`:
   - banco: `mat_temporis_lab`
   - usuário: `root`
   - senha: vazia, se o seu XAMPP estiver no padrão.
4. Acesse: `http://localhost/MAT_v6_Temporis_Lab/`
5. Para testar a importação, use `sample/import_demo.csv`.

## Fluxo do sistema

`CSV bruto -> marcos_raw -> normalização -> marcos em revisão -> duplicatas/fontes -> pontuação I/C/F -> aprovação -> Têmporis`

Nada importado entra diretamente no índice. A aprovação exige pontuação completa e pelo menos uma fonte vinculada para permitir `incluir_temporis = 1`.

## O que já existe

- Dashboard de qualidade e cobertura.
- Importação CSV em massa.
- Preservação integral do dado bruto.
- Mapeamento automático de cabeçalhos comuns.
- Normalização de texto e datas, inclusive intervalos e `a.C.`.
- Fila de revisão com filtros.
- Detecção automática de duplicatas por similaridade de nome + tolerância temporal.
- Mesclagem auditável de duplicatas.
- Cadastro e vínculo de fontes com papéis diferentes.
- Campos de civilização, país e cidade/localidade.
- Impacto, confiabilidade e fator fundacional com justificativas.
- Cálculo automático de `P_i = (I² × C/100) × F`.
- Quality gate: aprovação para Têmporis só é efetiva com fonte e pontuação.
- Cálculo da série histórica `K(t)` e `T(t)`.
- Alteração experimental de alpha e ano-base sem alterar a base.
- Trilha de auditoria.
- Tema claro/escuro e layout responsivo.
- PDO + prepared statements + CSRF + escape de saída.

## Decisões metodológicas importantes

- **Base bruta não é apagada.** A limpeza produz um registro separado e auditável.
- **Automação sugere; humano decide.** Duplicatas e aprovação não são decididas silenciosamente pelo algoritmo.
- **Fonte é entidade própria.** Um marco pode ter várias referências e cada referência pode ter um papel: origem, confirmação, data, impacto ou contexto.
- **A Têmporis só usa registros aprovados e explicitamente incluídos.**

## Próximas melhorias previstas

### Backend / ciência

- Importador XLSX nativo.
- Tela de mapeamento manual de colunas antes de confirmar a importação.
- Jobs em lote para bases com dezenas de milhares de registros.
- Similaridade semântica além de Levenshtein.
- Rubrica configurável para Impacto, Confiabilidade e Fator Fundacional.
- Concordância entre avaliadores (ICC / kappa) e múltiplos revisores.
- Datas probabilísticas/intervalos e propagação de incerteza por Monte Carlo.
- Grafo de dependências entre marcos para reduzir dupla contagem.
- Versionamento da fórmula Têmporis e reprodutibilidade por `run`.
- Backtesting rolling-origin, MAE/RMSE/sMAPE/MASE e comparação com baselines.
- Integração de validação externa com PCDB/Farmer-Lafond/OWID.
- Importadores específicos para Modis e Huebner, preservando origem e citação.
- API REST autenticada e exportação reproduzível de datasets.
- Controle de usuários, papéis e revisão cega.

### Design / UX

- Preview de importação antes de gravar.
- Edição em massa por categoria/período/fonte.
- Atalhos de teclado na fila de revisão.
- Diff visual entre dado bruto e limpo.
- Timeline interativa e filtros cruzados.
- Painel de cobertura temporal para detectar épocas sub-representadas.
- Painel de equilíbrio por domínio tecnológico.
- Wizard de revisão por lotes.

## Estrutura

- `dashboard.php`: visão geral e qualidade.
- `importar.php`: importação em massa.
- `revisar.php`: fila de revisão.
- `duplicatas.php`: resolução de pares suspeitos.
- `marcos.php` / `marco.php`: base consolidada e editor.
- `fontes.php`: referências e vínculos.
- `temporis.php`: cálculo e série histórica.
- `auditoria.php`: rastreabilidade.
- `api/`: ações assíncronas.
- `src/`: normalização, importação, auditoria, duplicatas e Têmporis.
- `database/schema.sql`: banco completo.

## Observação sobre a fórmula atual

O sistema implementa a fórmula que está no material atual do TCC: `P_i=(I_i² × C_i/100) × F_i`. Como a escala registrada de `C_i` é 1–10, o divisor 100 deve ser revisado metodologicamente: ele pode ser apenas um fator de escala, mas precisa ser documentado/justificado. A aplicação deixa essa decisão explícita para uma próxima versão em vez de esconder a questão.


## Correções 6.0.1-alpha
- Corrigido conflito de variável no cabeçalho que quebrava a Fila de revisão e a Base de marcos após a importação.
- Detecção de duplicatas passou a reconhecer acrônimos comuns (ex.: World Wide Web ↔ WWW).
- Adicionado botão **Reanalisar duplicatas** para recalcular os pares já importados.


## Correções 6.0.2-alpha

- Corrigido o reprocessamento de duplicatas já revisadas.
- Pares marcados como **Não são duplicatas** não voltam a aparecer ao clicar em **Reanalisar duplicatas**.
- Pares já **mesclados** também não são recriados.
- Registros com status `duplicado` foram excluídos da busca de novos candidatos.
- A reanálise remove e recria apenas sugestões ainda pendentes, preservando decisões humanas anteriores.


## v6.1.0-alpha — filtro científico da Têmporis

Esta versão separa explicitamente o **banco geral do MAT** da **base que pode entrar no Índice Têmporis**.

Novos campos por marco:

- natureza: tecnológico, descoberta científica, método/infraestrutura, social/político, biológico/evolutivo, cosmológico, misto etc.;
- sugestão automática de elegibilidade;
- decisão humana de elegibilidade (`sim`, `não`, `revisar`);
- justificativa de escopo;
- flag de escopo revisado e versão do classificador.

A triagem automática usa regras transparentes por palavras-chave e **nunca aprova um marco sozinha**. Somente registros com escopo revisado como `SIM`, fonte vinculada, pontuação completa e status aprovado podem compor a Têmporis.

Ao abrir a versão 6.1 sobre um banco 6.0.x, a aplicação adiciona as novas colunas automaticamente. Registros já existentes podem receber sugestões pela página **Escopo científico > Recalcular sugestões automáticas**.

O diretório `sample/` inclui `modis_2025_14_marcos_mat.csv`, um pequeno conjunto real para testar o fluxo antes de importar bases maiores.


## Correção 6.1.1-alpha

- A tela **Escopo científico** agora permite decidir diretamente cada registro com **Aprovar escopo** ou **Recusar escopo**.
- A decisão rápida marca `escopo_revisado=1` e grava a ação na auditoria.
- **Aprovar escopo** não equivale a aprovar o marco inteiro: pontuação, fonte e aprovação final continuam sendo etapas separadas.
- **Recusar escopo** mantém o registro no MAT como contexto e garante `incluir_temporis=0`.
- O nome do pacote passa a permanecer `MAT_v6_Temporis_Lab.zip` nas próximas atualizações para facilitar substituição.

## v6.2.0-alpha — Coletor de dados em escala

Esta versão adiciona uma camada para construir a Base Zero com centenas ou milhares de candidatos sem cadastro manual.

### Novo: Coletor de dados
- Tela **Coletor de dados** no menu.
- Integração ao vivo com **Wikidata Query Service (SPARQL)**.
- Presets para itens com `P575` (data de descoberta/invenção), opcionalmente exigindo `P61` (inventor/descobridor), ou itens classificados como invenção.
- Filtro por intervalo de anos, limite e offset.
- Pré-visualização antes de importar.
- QID, URL e payload bruto preservados.
- Chave externa única evita reimportar o mesmo QID indefinidamente.
- Tudo entra como **candidato em revisão**; nenhum registro vira Têmporis automaticamente.

### Escala e segurança
- Para 1.000–2.000 marcos, trabalhar em lotes por período evita timeouts do Wikidata e facilita auditoria.
- O Wikidata é usado como fonte de descoberta/metadados, não como validação científica única.
- Confirmações acadêmicas/institucionais devem ser anexadas depois na tela de Fontes.

### Novo: exclusão segura de lote
A tela **Importar base** agora permite excluir um lote completo, incluindo os marcos que vieram dele, sem usar o phpMyAdmin. A operação passa por CSRF, transação e auditoria.

### Atualização do banco
Não é necessário importar SQL manualmente em instalações existentes: `Schema::ensure()` cria as novas colunas/tabelas automaticamente quando o MAT abre. O arquivo `database/update_v6_2.sql` fica disponível para auditoria/reprodução.

## Segurança de coleta — v6.2.1-alpha

- O coletor externo agora exige **pré-visualização obrigatória** antes de qualquer gravação.
- Depois da prévia, a importação só é liberada com checkbox de confirmação + digitação de `IMPORTAR` + confirmação final do navegador.
- Se os filtros forem alterados depois da prévia, a importação é bloqueada e uma nova prévia é exigida.
- A prévia expira em 15 minutos.
- A tela do Coletor agora possui **Excluir lote** para desfazer uma coleta acidental sem usar phpMyAdmin.
- A exclusão do lote continua auditada e remove somente os registros daquele lote.


## v6.3.0-alpha — Importador científico inteligente

O MAT agora importa diretamente **CSV, TXT e TSV** sem exigir conversão manual prévia.

Fluxo:

1. O usuário envia o arquivo.
2. O MAT detecta automaticamente o separador (TAB, `;`, `,` ou `|`).
3. O sistema identifica e sugere o mapeamento das colunas.
4. Uma prévia obrigatória mostra os dados antes de qualquer gravação.
5. O usuário pode corrigir manualmente o mapeamento.
6. A importação só é liberada após marcar que revisou e digitar `IMPORTAR`.
7. A linha original completa permanece preservada em `marcos_raw`.
8. Os registros entram como candidatos em revisão, nunca como marcos aprovados automaticamente.

Mapeamentos adicionados para bases científicas incluem `Invention`, `InnovationType`, `Inventors`, `Organization`, `Environment`, `Country`, `Source` e `Place Name, Province, State`. O arquivo `DataS1.txt` foi usado como teste real: o MAT detectou TAB, 24 colunas, 417 registros e mapeou automaticamente os campos principais.

Novos campos consolidados: `inventores`, `organizacao` e `ambiente`.

## v6.4.0-alpha — Triagem científica v2

Esta versão acelera a separação de centenas ou milhares de candidatos sem transformar a decisão automática em aprovação científica.

### Classificador bilíngue ampliado
- Dicionário interno muito maior em português e inglês para computação, eletrônica, comunicação, energia, transporte, indústria, materiais, agricultura, medicina, biotecnologia, instrumentação e descobertas científicas.
- Regras negativas específicas para eventos políticos/sociais, eventos biológicos naturais e eventos cosmológicos.
- Expressões científicas como `theory of evolution`, `DNA structure`, `quantum mechanics`, `periodic table` e equivalentes em português são tratadas como ciência, não como simples eventos naturais.

### Mais do que palavras-chave
A triagem passa a combinar vários sinais transparentes:
- **categoria da base** recebe peso alto;
- **nome do marco** recebe peso maior que descrição;
- **descrição** serve como evidência complementar;
- categorias estruturadas como `Industrial`, `Agricultural`, `Medical`, `Transportation`, `Communication`, `Household` e `Armaments` recebem prioridade tecnológica;
- conflitos entre sinais positivos e sinais fora do escopo vão para revisão humana;
- cada registro recebe `scope_score` e `scope_confidence` (`alta`, `media`, `baixa`).

### Dicionário de triagem editável
Nova tela **Dicionário de triagem** permite adicionar, pausar e remover regras próprias sem editar PHP. Cada regra informa:
- palavra ou expressão;
- natureza sugerida;
- peso;
- campo onde procurar (nome, categoria, descrição ou qualquer campo);
- idioma;
- observação opcional.

As regras próprias afetam somente a sugestão automática. A aprovação final do escopo continua obrigatoriamente humana e auditável.

### Teste com DataS1
No arquivo `DataS1.txt` usado durante o desenvolvimento, as 417 linhas possuem categorias de inovação explícitas. Com a triagem v2, 417/417 foram reconhecidas como candidatas científico-tecnológicas para revisão, sendo 325 com confiança alta e 92 com confiança média. Isso é uma **triagem**, não uma aprovação automática do conteúdo nem da relevância para a Têmporis.

### Revisão humana por lote
Para bases grandes, a página **Escopo científico** agora oferece **Revisar escopo por lote**. O usuário escolhe obrigatoriamente um lote de importação, pode restringir por categoria, sugestão e confiança, vê uma amostra antes da ação e precisa marcar que revisou e digitar `APROVAR` ou `RECUSAR`. Essa ação decide somente o **escopo**; não aprova o marco final nem o inclui automaticamente na Têmporis.
