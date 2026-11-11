# Backtesting com leitura simplificada (09/11/2026)

Esta atualização altera a apresentação da página `index.php?page=backtesting`.
O cálculo, os parâmetros numéricos, os 11 modelos e a consulta somente leitura foram preservados.

## O que aparece sem clicar em nada

- Horizonte escolhido (10, 25, 50 ou 100 anos) e botão para recalcular.
- Identificação da fonte efetivamente utilizada, com aviso para exportação estática.
- Resposta simples: qual modelo apresentou o menor WAPE calculável no recorte atual.
- Resumo com período previsto, total de modelos com resultado e número de testes históricos.
- Comparação visual resumida dos três menores erros, com WAPE e amostras.
- Um exemplo concreto do modelo em destaque: previsão do crescimento de K x crescimento observado (último corte do modelo).

## O que ficou em “Mostrar mais”

1. Fonte de dados e coorte (filtros avançados).
2. Tabela dos 11 modelos, incluindo WAPE, MAE, viés e testes válidos.
3. Tabelas por corte histórico de cada modelo.
4. Explicação matemática, limitações da seleção retrospectiva e da base histórica.

## Cuidados metodológicos

WAPE é o somatório dos erros absolutos dividido pelo somatório das variações observadas de K.
Não é porcentagem de acertos ou probabilidade de sucesso futuro. A interface indica que
um menor WAPE não prova superioridade futura nem validade externa do Índice Têmporis.
Se modelos tiverem números diferentes de períodos válidos, a página avisa.
O exemplo “previsto x observado” corresponde a um único corte, não ao WAPE geral.

## Como abrir

Acesse `http://localhost/MAT_Refinado_0_1/index.php?page=backtesting`.
Se o MySQL estiver indisponível, no filtro avançado você pode selecionar a exportação
histórica (snapshot), que é uma cópia fixa e não representa mudanças na curadoria ativa.

Testes automatizados de interface e cálculo: `php tests/test_reorganizacao_temporis.php`,
`php tests/test_backtest_temporis.php`. Validar a conexão ao banco real no seu XAMPP.
