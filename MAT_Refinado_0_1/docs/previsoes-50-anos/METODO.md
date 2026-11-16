# MAT · Melhores previsões · 50 anos — Método e fórmula

Documentação da aba `index.php?page=destaques_previsoes` (tela `views/destaques_previsoes.php`).
Objetivo: deixar registrada, em um lugar fixo, a origem de cada número exibido.

> Linguagem conservadora: os números abaixo são **registros históricos** de testes
> anteriores, não leituras da curadoria atual. Nada nesta tela altera o banco.

---

## 1 · Peso de cada marco (Fórmula Final)

Cada marco recebe um peso calculado a partir de três das 11 rubricas documentadas
no dossiê (impacto, confiabilidade histórica e relevância fundacional):

```
w = (I / 10)² × C × F
```

- **I** — Impacto (soma das notas de impacto, escala interna até 12)
- **C** — Confiabilidade histórica (soma até 12)
- **F** — Relevância fundacional (soma até 9)

## 2 · Índice Têmporis (MAT Final)

```
K(t) = Σ wᵢ   (marcos datados até o ano t)
```

O índice bruto **não depende de ano-base**; a normalização (ex.: 1900 = 100) é
apenas uma forma opcional de exibição.

## 3 · Previsão — modelo exponencial local, horizonte de 50 anos

Implementado em `app/TemporisFinal.php` (`TemporisFinal::projetar`, modelo
`exponencial50`). A taxa de crescimento dos **últimos 50 anos observados** é
extrapolada por exponencial:

```
K̂(t + h) = K(t) × ( K(t) / K(t − 50) )^(h / 50)
```

Para o horizonte fixo desta aba (h = 50), a expressão simplifica para:

```
K̂(t + 50) = K(t)² / K(t − 50)
```

## 4 · Como os erros exibidos são calculados

- **Aumento real** do período: `g_obs = K(t + 50) / K(t) − 1`
- **Crescimento previsto**: `g_prev = K̂ / K(t) − 1`
- **Erro no crescimento** (o percentual dos cartões/tabelas):
  `erro = |g_prev − g_obs| / g_obs`, em %

O **WAPE** é a média ponderada dos erros absolutos dos 12 cortes de 50 anos
avaliados no snapshot; não se confunde com o melhor erro individual.

## 5 · Origem dos dados das três bases

| Base | Origem | Marcos | Situação |
|---|---|---|---|
| **07/11/2026** | Exportação fixa (snapshot) incluída no MAT — 800 carregados, 667 elegíveis | 667 | **Reproduzida** por `tests/test_previsoes_50_anos_snapshot.php` (3 cortes conferidos: 5,92% · 6,08% · 0,19%; WAPE 31,13%) |
| **12/11/2026** | Ensaio anterior com a Fórmula Final, registrado para o TCC | 1.257 informados | Registro preservado; **ainda não reexecutada** neste ambiente (WAPE informado: 29,20%) |
| **16/11/2026** | Banco da curadoria `mat_temporis_curadoria` (`cur_candidatos` + `cur_criterios`, 16.918 registros de critérios) | 1.292 elegíveis de 1.538 | **Reproduzida ao vivo e validada por ferramenta externa** (ver §6.1) — 3 cortes: 1,94% · 1,28% · 16,65%; WAPE 29,16% |

As três bases **não são intercambiáveis**: contagens e pesos diferem, e
cada uma segue registrada separadamente, sem mistura.

## 6 · Conferência de hoje (o melhor dos dois mundos)

A aba une os dois universos: a vitrine preserva os registros históricos
(estáticos, nunca "quebram"), e a seção **“05 / Conferência de hoje”**
recalcula os mesmos três cortes (1850→1900, 1875→1925, 1900→1950) **ao vivo
no banco da curadoria** (`TemporisFinal::obterDados('auto')` → `mat_temporis_curadoria`),
na mesma métrica. Se o MySQL estiver indisponível, a conferência deixa de
aparecer e os registros históricos permanecem — a tela nunca perde os números.

As diferenças entre os dois lados são esperadas: o banco evolui desde o
snapshot (marcos novos, revisões de datas e notas). A conferência é
somente leitura e não valida nem rejeita marcos.

### 6.1 · Registro fixo da base 16/11 e validação cruzada independente

A seção **“04 / Registro da curadoria de 16/11/2026”** preserva o retrato
do dia: cortes 1850–1900 (1,94%), 1875–1925 (**1,28%**, menor erro) e
1900–1950 (16,65%), com aumentos reais de +51,89% / +58,43% / +62,25%.

**Validação cruzada:** a mesma base (tabelas `cur_candidatos` e
`cur_criterios`) foi reprocessada por **ferramenta externa, com o motor
matemático original, sem alterar pontuações nem selecionar resultados**.
A implementação externa produziu exatamente os mesmos números — os três
erros (1,94% · 1,28% · 16,65%), o mesmo WAPE (29,16%) e as mesmas
contagens (1.292 de 1.538 elegíveis, 16.918 critérios). Nenhuma
divergência foi encontrada entre as duas implementações. Esta
independência está anotada como evidência para o TCC.

Nota honesta: o erro de 0,19% para 1900–1950 (base 07/11) **não se
repetiu** na base 16/11 — o mesmo corte dá 16,65% na base atual. As bases
são distintas e seguem registradas separadamente.

## 7 · Onde conferir no código

| Arquivo | Papel |
|---|---|
| `app/TemporisFinal.php` | Peso, K(t), modelos de previsão (`MODELOS`, `projetar`), snapshot |
| `tests/test_previsoes_50_anos_snapshot.php` | Reproduz os 3 cortes da base 07/11 e o WAPE dos 12 |
| `views/destaques_previsoes.php` | Tela da aba (estática, sem consulta ao banco) |
| `assets/destaques-previsoes.css` | Estilo exclusivo da aba |
| `index.php` | Rota `destaques_previsoes` e rótulo do menu |

Documentação geral do índice: `index.php?page=metodo` (Caderno de fórmulas).
Testes de previsões concorrentes: `index.php?page=backtesting`.

> **Padrão da Backtesting:** o teste inicial abre na **base histórica de
> 07/11** (snapshot, 667 elegíveis) com o modelo `exponencial50` e horizonte
> 50 — a mesma seleção desta aba — de modo que os três primeiros cortes
> reproduzam 5,92% · 6,08% · 0,19%. Para o banco vivo, escolher a fonte
> *curadoria (MySQL)* na página (`fonte=auto` ou `fonte=curadoria`); a aba de
> destaques mantém a conferência ao vivo sempre ligada ao banco (`auto`).
