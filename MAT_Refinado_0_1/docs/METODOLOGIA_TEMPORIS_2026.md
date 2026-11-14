# MAT · Metodologia Têmporis vigente (12 de novembro de 2026)

## Onde acessar no projeto

- `index.php?page=temporis`: **índice bruto K(t)** por padrão, consultas de anos, evolução 1700–2026, velocidades, detalhes da curadoria e transformações opcionais.
- `index.php?page=projecoes`: motor compartilhado, cortes históricos, projeções por modelo e horizonte, comparação de métodos nos mesmos cortes, WAPE, erro isolado e gráfico cronológico.
- `index.php?page=backtesting`: comparação exploratória dos onze modelos, coortes e cortes individuais. Inclui comparação justa das variantes de 50 e 100 anos e teste de seleção progressiva.
- `index.php?page=demonstracao_formulas`: explica o cálculo do peso e **K(t) bruto**; apresenta as transformações legadas como comparações opcionais.
- `index.php?page=metodo`: fundamentação, três versões históricas e ressalva de que a formulação vigente não exige ano-base.
- Tela inicial e menu: atalhos apontam para a implementação compartilhada.
- `mat_proj/index.php`: URL histórica redireciona para o motor novo. Código anterior: `docs/historico/projecoes_antes_metodologia_2026.php.txt`. A seção legada `temporis_reformulado` continua identificada como **histórica**, não como padrão vigente.

## Equações e regra única

As notas dos 11 critérios são as **notas já presentes na curadoria**. Não se altera nota, status de aprovação, tabela SQL ou fonte.

- Impacto: `I = 1 + 9*S_impacto/12` (quatro critérios de 0 a 3).
- Confiabilidade: `C = S_confiabilidade/12` (quatro critérios de 0 a 3).
- Fundacional: `F = 1 + 0,5*S_fundacional/9` (três critérios de 0 a 3).
- Peso do marco: `w_i = (I_i/10)^2 * C_i * F_i`.
- **Índice principal**: `K(t) = soma(w_i para cada marco com ano_i <= t)`.
- Índice de comparação **opcional**: `N_b(t) = 100*K(t)/K(b)` se `K(b)>0`; o ano 1900 é exemplo, não é referência obrigatória e não participa da predição.
- Incremento: `DeltaK(a,b) = K(b)-K(a)`.
- Velocidade média: `V_j(t) = (K(t)-K(t-j))/j`, em pontos/ano, com `j` igual a 25 ou 50.

Ao exibir intervalos de 50 anos, o último é **2000–2026**, com 26 anos. Portanto não compare somente `DeltaK`: compare também velocidade por ano.

## Projeções e backtesting

1. Congelar **fonte, coorte, versão da curadoria, filtros, 11 rubricas e pesos**.
2. Determinar corte `c` e horizonte `h`; o motor só usa `K(t)` para `t<=c` ao ajustar o modelo.
3. Modelos de janela local: linear, exponencial; há cenários adicionais rotulados experimentais (controle sem crescimento, aceleração, ímpeto, logístico, Gompertz etc.).
4. `Kprevisto(c+h)` é calculado **na unidade bruta**. Uma escala relativa pode ser aplicada depois, somente para exibição.
5. Quando `c+h<=2026`, verificar a previsão em relação ao **valor registrado no catálogo atual**. Se o alvo está no futuro, o erro ainda não é observável; não apresentar erro futuro inventado.
6. **Erro por corte**: `abs(DeltaK_previsto - DeltaK_registrado) / DeltaK_registrado * 100` quando incremento real > 0.
7. **WAPE agregado**: `100 * soma(abs(DeltaK_previsto-DeltaK_registrado)) / soma(DeltaK_registrado)` nos cortes de validação elegíveis. Não equivale a porcentagem de certeza ou faixa de erro futuro.
8. Para comparar linear/exponencial com janelas 50/100, pontuar os quatro **exatamente nos mesmos cortes**. A melhor variante escolhida após olhar os erros possui possível viés de seleção. O experimento adicional `selecaoProgressiva` escolhe modelos só após resultados de cortes anteriores estarem disponíveis.

## O que continua não validado

- **Viés de conhecimento posterior:** mesmo que o algoritmo não use `K(t)` de depois do corte na previsão, as pontuações e seleções dos marcos foram feitas hoje. Logo, os backtests são *retrospectivos e exploratórios*, não provas de previsão feita no passado.
- **Cobertura desigual:** regiões e épocas antigas e anos mais recentes podem estar menos documentados; adicionar marcos altera valores históricos antigos. `K(t)` é estoque *catalogado e ponderado*, não medida de toda inovação mundial.
- **Falta de auditoria independente:** `APROVADO` no sistema é status administrativo da curadoria; não significa homologação externa.
- **Margem de erro futura:** WAPE é erro agregado retrospectivo. Não há intervalo probabilístico calibrado (por exemplo IC 95%) no módulo; não inventar limites futuros.
- **Modelos de longa extrapolação:** especial cuidado para horizontes de 50 e 100 anos; a extrapolação exponencial pode crescer muito sem que isso seja evidência empírica do futuro.
- **Peso quadrático e bônus fundacional:** escolhas autorais passíveis de testes de sensibilidade; não são constantes comprovadas por literatura.

## Quais dados estão no ZIP?

Este ZIP contém um snapshot histórico local de **800 candidatos, 667 elegíveis**; é apenas um conjunto de teste de 07/11/2026 e **não contém necessariamente os 1.390 candidatos/1.257 elegíveis** presentes no MySQL atual do pesquisador. Em execução com MySQL, a fonte automática busca a curadoria atual; se indisponível, a interface avisa explicitamente que utiliza o snapshot. Nunca confundir resultados de conjuntos de dados diferentes.

Nenhum SQL da curadoria foi modificado nesta atualização. Não importar ou executar esquemas SQL para ativar o método; ele é instalado com a substituição dos arquivos PHP.

## Como testar no XAMPP

1. Faça um backup do diretório atual do MAT e do banco MySQL antes de copiar arquivos.
2. Extraia o ZIP na pasta `C:\xampp\htdocs\MAT_Refinado_0_1` mantendo a estrutura. O pacote distribuído NÃO inclui `config.local.php` (credenciais privadas): preserve o arquivo já existente ou copie-o do backup para uma instalação nova. Não crie uma segunda subpasta aninhada por engano.
3. Inicie Apache e MySQL. Abra `http://localhost/MAT_Refinado_0_1/index.php?page=temporis&fonte=curadoria`.
4. Confirme que a fonte exibida é o MySQL da curadoria, e não `snapshot`. Verifique contagem de candidatos e K(2026). Números variam conforme o catálogo selecionado.
5. Entre em `index.php?page=projecoes`: experimente corte 1850, horizonte 50, depois corte 2026 e horizonte 25; no último, não deve haver erro futuro observado.
6. Entre em `index.php?page=backtesting`: troque 10, 25, 50, 100 anos, fonte e coorte; veja números de testes e WAPE nos mesmos cortes.
7. Na pasta do projeto, execute `C:\xampp\php\php.exe tests\test_metodologia_temporis_2026.php` e os testes antigos `tests\test_temporis_final.php` e `tests\test_reorganizacao_temporis.php`.

**Validação realizada nesta entrega:** testes PHP unitários, checagem estática de sintaxe e abertura HTTP local das páginas com snapshot. **Não foi possível conectar ao seu MySQL do XAMPP neste ambiente**; a instalação específica do banco e suas permissões devem ser testadas no computador de destino.
