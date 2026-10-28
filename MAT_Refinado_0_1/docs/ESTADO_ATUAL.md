# Estado atual — MAT Observatório v6.2

**Funcional:** identidade editorial, acervo, dossiês, cronologia, atlas, panfletos, exportações, autenticação básica, importação CSV em staging, Têmporis experimental, classificação de escopo (sugestão e parecer), sugestão de rubricas, catálogo e vínculos de fontes, detecção de possíveis duplicatas, trilha de revisão.

**Testado aqui:** sintaxe PHP, cálculos de núcleo, classificação, duplicatas por siglas e pré-codificação. Também foram verificadas respostas HTTP das páginas em modo demonstração.

**Não verificado aqui:** execução de consultas e gravações em MariaDB/MySQL reais (ambiente sem `pdo_mysql`); qualidade histórica de marcos; equivalência científica das escalas; migração dos dados pessoais em execução; importações ao vivo de serviços externos; publicação GitHub.

**Próximas prioridades:** validar instalador e migração na sua máquina, ampliar catálogo bibliográfico, revisar decisões e critérios da Têmporis, implementar experimentos históricos e modelagem comparativa. A estética do Observatório fica estável para os próximos commits.

## Atualização corretiva — 0.1.1 (28/10/2026)

- `temporis_series` agora ordena registros internamente, evita que eventos posteriores
  contaminem `K_base` e ignora datas ausentes/inválidas, sem convertê-las em ano zero.
- `Methodology::normalizeCriterion` passa a rejeitar notas fracionárias e entradas
  não numéricas, impedindo truncamento silencioso; campos vazios continuam pendentes.
- Nova suíte `tests/test_regressoes_0_1_1.php` verifica cronologia, dados ausentes,
  datas a.C., invariância de ordenação, rubrica e preservação das equações.
- **Não alterados:** método A, método B, α=0,65, β=2 base, fontes e banco de dados.
- **Ainda pendentes:** paridade total do Lab, testes MySQL no XAMPP, calibração de
  escalas/ano-base, sete evidências de suporte e validação científica da Têmporis.
