# Protocolo MAT-Proj — experimentos da fase 0.1

## Questão e hipótese

**Pergunta:** dados observados apenas parcialmente, combinados com a série histórica da mesma unidade, conseguem estimar melhor o restante de um indicador de adoção tecnológica que a persistência do valor anterior?

**H0:** o MAE do modelo proposto é maior ou igual ao MAE do modelo ingênuo.

**H1:** o MAE do modelo proposto é menor que o MAE do modelo ingênuo nas condições pré-registradas. Essa hipótese ainda não foi testada com dados reais neste pacote.

## Unidade, variável e fonte

- Unidade: país com código de três letras e observações anuais.
- Indicador: percentual da população que utilizou internet nos últimos três meses, segundo os metadados da ITU/Banco Mundial.
- Origem para importação: https://ourworldindata.org/grapher/share-of-individuals-using-the-internet
- Observação: os dados podem ser revisados retroativamente. Registrar data de coleta e hash do arquivo.
- Escopo: indicadores de adoção; não quantificação de marcos nem Têmporis.

## Protocolo principal

1. Registrar base e hash antes de avaliar desempenho.
2. Definir ano-alvo: 2017 a 2024 para retrospectivas. Excluir países sem observações do ano anterior e do alvo (limitação explicitada).
3. Estratificar somente com informação disponível **anterior** ao ano-alvo.
4. Selecionar 5%, 10%, 20%, 40%, 60% de observações, sem usar o valor atual na seleção.
5. Simular seleção proporcional por estrato e seleção enviesada, preservando sementes.
6. Gerar previsão dos ocultos e registrar a estimativa **antes de revelar o valor real**.
7. Calcular MAE, RMSE e melhoria (ou piora) perante o modelo ingênuo por ano e por faixa.
8. Para o experimento temporal, ocultar todos os valores do ano-alvo e treinar apenas com anos anteriores; realizar validação temporal progressiva.
9. Reportar também períodos de piora e cobertura de unidades; não selecionar somente anos favoráveis.

## Limitações obrigatórias

- Observações publicadas podem não ter o mesmo momento de disponibilidade; os retrotestes atuais usam **valores históricos consolidados**, não os dados como eram conhecidos na data original.
- Os grupos definidos pela taxa anterior não representam necessariamente diferenças de crescimento; testar alternativas.
- A amostra de unidades completas favorece países com melhor cobertura estatística.
- O tamanho de amostra corresponde à quantidade de países, não à população, e a média dos países não é taxa global.
- Não afirmar que esse método prevê novas invenções ou que valida diretamente a Têmporis.
- Não comparar numericamente seu erro com 0,1 ponto percentual do Apuração Aberta, pois variáveis e unidades são diferentes.

## Critérios de progressão

Apenas após testes com dados públicos e resultados reprodutíveis, e se o modelo superar consistentemente o baseline em múltiplos períodos, partir para dados científicos e outras séries. Se houver falhas, registrar os casos e revisar o modelo sem ocultar o resultado.
