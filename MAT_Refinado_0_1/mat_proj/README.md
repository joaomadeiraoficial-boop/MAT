# MAT-Proj 0.1.0 — Estação de projeções

**Experimento independente do Índice Têmporis.** Adição isolada à pasta `MAT_Refinado_0_1/mat_proj/`. Nenhum arquivo ou tabela existente do MAT foi substituído, e os parâmetros da Têmporis não foram alterados.

## Objetivo

Investigar se uma **referência histórica por unidade** e a **variação observada em uma fração das unidades** permitem estimar informações ainda não observadas. O método é **inspirado no princípio publicamente descrito** pelo [Apuração Aberta](https://apuracaoaberta.com.br/), e **não reproduz seu código, transferências de votos, estratos eleitorais ou precisão divulgada**.

### Duas experiências separadas

1. **Reconstrução histórica:** para um ano conhecido, o sistema mantém os percentuais do ano anterior para todos os países, revela o valor atual de cerca de 20% deles (amostra estratificada), estima os demais e compara com a realidade que foi propositalmente escondida.
2. **Previsão de ano seguinte:** utiliza apenas dois anos anteriores para estimar o próximo. Para anos já registrados, faz retroteste; para 2026, caso a base contenha até 2025, apresenta previsões **sem qualquer alegação de erro conhecido**.

A primeira tarefa se parece com a lógica de fechamento de uma apuração parcial; a segunda é previsão temporal genuína, mais difícil.

## Instalação no seu Windows / XAMPP

1. **Não extraia por cima do antigo MAT.** Faça uma cópia ou tenha os commits do projeto já salvos.
2. Baixe o ZIP de MAT-Proj, coloque-o em `D:\Faculdade nero\xampp\htdocs` e **extraia o ZIP dentro da raiz `MAT_Refinado_Git`**. O ZIP já contém o caminho `MAT_Refinado_0_1\mat_proj\` e um documento adicional em `MAT_Refinado_0_1\docs\`.
3. Com Apache ligado, abra:

   `http://localhost/MAT_Refinado_Git/MAT_Refinado_0_1/mat_proj/`

4. O laboratório inicia com **120 unidades fictícias e dados inteiramente sintéticos**, para ser testável imediatamente. **NÃO interpretar esses números como dados científicos reais.**
5. O ambiente pode executar testes sem banco:

```powershell
cd "D:\Faculdade nero\xampp\htdocs\MAT_Refinado_Git"
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\tests\test_engine.php
```

### Usar dados científicos reais

**Fonte:** [Share of the population using the Internet](https://ourworldindata.org/grapher/share-of-individuals-using-the-internet), da ITU / Banco Mundial, organizada pela Our World in Data (1990–2025, fonte atualizada em 27/07/2026). Mede **porcentagem de pessoas que usaram internet**, não volume de invenções ou Índice Têmporis.

**Opção A, sem automação:** use o link **Baixar dados reais (OWID)** na própria página, selecione `Meu arquivo CSV`, escolha o arquivo e execute. O upload é temporário e não altera o MySQL.

**Opção B, coleta automática acionada pelo pesquisador:** no PowerShell:

```powershell
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\cli\atualizar_dados.php
```

Caso o XAMPP não tenha acesso à internet, baixe o CSV pelo navegador e use:

```powershell
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\cli\atualizar_dados.php "D:\Downloads\share-of-individuals-using-the-internet.csv"
```

O coletor verifica as colunas, número de países, quantidade de anos e calcula SHA-256 do arquivo. Os dados baixados ficam apenas no computador (`data/owid_internet.csv`, ignorado no Git). A conexão externa **não foi testada neste ambiente**: requer teste no seu XAMPP. O indicador tem países faltantes e revisões; por isso não representa automaticamente toda a população mundial.

### Terminal (reprodutibilidade)

```powershell
# Reconstruir dados sintéticos de 2022 (20% de países observados)
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\cli\simular.php
# Retroteste de previsão de um ano com dados reais já baixados
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\cli\simular.php .\MAT_Refinado_0_1\mat_proj\data\owid_internet.csv 2023 forecast
```

O resultado do CLI é JSON, adequado para arquivamento dos experimentos.

## Fórmulas do MVP

Cada unidade (país) tem um valor histórico \(x_{i,t-1}\) e, se revelado, valor atual \(x_{i,t}\). Medimos a variação de **pontos percentuais**, \(\Delta_i=x_{i,t}-x_{i,t-1}\), em vez de estimar números absolutos do zero.

Os países são agrupados pelo **percentual anterior** em faixas fixas [0,25), [25,50), [50,75), [75,100]. A variação para o grupo é suavizada para a média geral observada com constante experimental \(\lambda=8\):

\[
\widetilde\Delta_g=\frac{n_g\overline\Delta_g+\lambda\overline\Delta}{n_g+\lambda},\qquad \hat x_{i,t}=\operatorname{clip}_{0,100}(x_{i,t-1}+\widetilde\Delta_{g(i)})
\]

**Previsão sem informações do ano-alvo:** usa o último incremento próprio e o incremento suavizado, treinados exclusivamente até \(t-1\):

\[
\hat x_{i,t}=\operatorname{clip}_{0,100}\left[x_{i,t-1}+0{,}5(x_{i,t-1}-x_{i,t-2})+0{,}5\widetilde\Delta_{g(i)}\right].
\]

Os parâmetros **0,5, \(\lambda=8\)** e os limites das faixas são **hipóteses de implementação**, ainda não calibradas nem cientificamente justificadas. NÃO afirmar que estes são os mesmos parâmetros do Apuração Aberta.

## Validação honesta

- **Modelo ingênuo:** mantém o valor do ano anterior (exceto unidades atuais já reveladas).
- **Erros:** MAE e RMSE em pontos percentuais dos valores **ocultos** na reconstrução e dos países com valor-alvo disponível na previsão.
- **Teste anti-vazamento:** modifica os valores históricos que deveriam estar ocultos e verifica que as previsões continuam idênticas.
- **Reprodutibilidade:** seleção estável por SHA-256, ano, semente e código do país.
- **Amostra:** fração de **países**, não de pessoas; o resumo exibe a **média simples dos países**, NÃO a participação mundial. Apenas países com valores necessários em anos consecutivos são incluídos. Esse filtro retrospectivo pode produzir viés de cobertura.
- **Ordem tendenciosa:** simula o aparecimento inicial de países com maiores níveis de acesso, revelando limites do método.
- **Retrotestes:** tabela anual 2017–2024. NÃO houve ajuste dos hiperparâmetros a esses períodos nem medição independente do desempenho real até a primeira execução com dados públicos.
- **Incerteza:** ainda não existe intervalo de confiança calibrado. As projeções pontuais não devem ser apresentadas como probabilidades.

## Próximos desenvolvimentos (não realizados)

1. Congelar instantâneo da base real com fonte, data e SHA-256; testar diversos períodos e cenários de seleção.
2. Avaliar estratificação por região e população (dependente de dados auxiliares e critérios compatíveis).
3. Fazer teste temporal mais rigoroso sem escolher parâmetros a partir do próprio conjunto de validação.
4. Intervalos de previsão calibrados e medição de cobertura.
5. Se a metodologia superar o baseline de maneira consistente, estudar relação entre os resultados e os índices externos — **sem forçar conexão matemática com a Têmporis**.
6. **Integração de navegação:** acesso pelo menu lateral e pela abertura do Observatório, sem substituir nenhum outro item (patch de integração MAT-Proj 0.1.1).

## Arquivos

- `index.php`: painel interativo, com estética do Observatório.
- `engine.php`: algoritmos separados da interface.
- `tests/test_engine.php`: testes da seleção, regressão, validação de formato e vazamento.
- `cli/atualizar_dados.php`: coletor manual com validação do CSV.
- `cli/simular.php`: ensaios reproduzíveis em JSON.
- `data/EXEMPLO_SINTETICO.csv`: **dados inventados, exclusivos de demonstração**.
- `docs/PROTOCOLO_EXPERIMENTAL.md`: protocolo acadêmico.
