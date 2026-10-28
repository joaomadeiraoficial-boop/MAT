# MAT — Versão Refinada 0.1
## Roadmap geral | Os marcos do desenvolvimento

**Nome científico mantido:** MAT — Modelagem da Aceleração Tecnológica.  
**Identidade do sistema:** Observatório do Tempo.  
**Versão de organização:** Refinada 0.1.0 (novo ciclo de desenvolvimento; **não** significa que a tecnologia v6.2.1 voltou à versão zero).  
**Data de congelamento do plano:** 26 de outubro de 2026.  
**Documento de referência operacional:** [`02_CADERNO_TECNICO.md`](02_CADERNO_TECNICO.md).  
**Estado:** planejamento organizado; os itens não marcados como concluídos **não** devem ser apresentados como entregues.

> **Propósito:** construir um observatório científico que organize marcos tecnológicos documentados, investigue como seu comportamento temporal evolui e submeta o Índice Têmporis a validações transparentes. A previsão é um módulo adicional, não um substituto da Têmporis.

### Decisões que não mudam sem registro

1. **O design autoral do Observatório é a base.** Não ressuscitar a interface genérica do Lab antigo; recuperar suas capacidades científicas.
2. **Nenhuma função do MAT v6 Têmporis Lab pode ser descartada silenciosamente.** Onde houver lacuna, registrar e implementar ou justificar tecnicamente a substituição, com teste de paridade.
3. **O `main` antigo não é sobrescrito.** O desenvolvimento começa em uma nova branch e somente é integrado após revisão.
4. **A Têmporis é preservada.** Pontuação do marco `Pᵢ`, acumulado `K(t)` e índice `T(t)` são o núcleo; modelagem temporal e previsão não são confundidas com o índice.
5. **Dado importado não é evidência aprovada.** O acervo piloto de 17 registros é um lote de candidatos; só marcos revisados e aprovados entram nos cálculos científicos.
6. **Relatar resultados contrários à hipótese é válido.** O MAT deve investigar aceleração, estabilidade, desaceleração e possíveis artefatos da amostragem.

## Marcos da jornada

| Marco | Entrega esperada | Prioridade | Situação / comprovação para encerrar |
|---|---|---|---|
| **M0 — Fundação refinada** | Renomear a fase; fixar dois documentos-guia; preservar código v6.2.1 e histórico do Lab antigo em branch separada | Obrigatória | **Preparada para commit**: documentos, pacote e testes locais sem MySQL |
| **M1 — Paridade com o legado** | Inventário funcional e restauração do importador inteligente, regras editáveis, lotes, mesclagem, coletor, rubricas, sensibilidade, auditoria e preferências de interface | Obrigatória | **Pendente**: testes de paridade para cada funcionalidade da matriz técnica |
| **M2 — Banco científico seguro** | Banco novo instalado, backup/restauração verificados, usuários, fontes, revisões, dados brutos e 17 candidatos importados corretamente | Obrigatória | **Parcial**: arquivos existem; integração no MySQL real pendente |
| **M3 — Curadoria confiável** | Critérios de inclusão/exclusão, conferência documental, tratamento de datas, duplicatas, justificativa dos 7 critérios e revisão humana | Obrigatória | **Pendente**: rubricar e avaliar amostra-piloto sem atribuir notas arbitrárias |
| **M4 — Têmporis consolidada** | Cálculos `Pᵢ`, `K(t)` e `T(t)` com parâmetros versionados, `K_base` explícito, dados válidos e testes independentes | Obrigatória | **Parcial**: motor experimental existe; calibração, paridade e validação pendentes |
| **M5 — História sob teste** | Intervalos, taxas por janela histórica, comparação linear/exponencial/logística, gráficos e resíduos | Obrigatória | **Pendente**: não confundir curva cumulativa crescente com aceleração |
| **M6 — Robustez e validação** | Sensibilidade a pesos e períodos, experimentos retrospectivos, comparação externa e análise do viés documental | Obrigatória | **Pendente**: critérios de aceite quantitativos definidos antes de olhar resultados |
| **M7 — MAT-Proj** | Modelo complementar de previsão, baseline simples, avaliação fora da amostra e incerteza calibrada | Complementar | **Pendente**: só após dados e M5–M6 suficientes |
| **M8 — Automação assistida** | Busca de candidatos e atualizações agendadas com fila de revisão e rastreabilidade, nunca aprovação opaca | Complementar | **Pendente**: fontes, termos de uso, logs e rollback primeiro |
| **M9 — Entrega acadêmica** | Sistema estável, repositório legível, metodologia escrita, resultados reproduzíveis, limitações e release | Obrigatória | **Pendente**: defesa baseada em resultados efetivamente obtidos |

**Critério de conclusão acadêmica:** é possível concluir um TCC sólido com M0–M6 e M9; **M7–M8 não podem inviabilizar a entrega**. A extração integral de uma cronologia extensa não é pré-requisito: começar pela amostra rastreável de 17 acontecimentos e expandir somente depois de validar o protocolo.

## Próximas entregas no Git (ordem)

- [x] **Preparação local:** inventário das versões recebidas, nova identidade de versão e documentação-mestra. A publicação no GitHub ainda precisa ser executada.
- [ ] **Commit 01 — documentos:** `docs: define escopo e roadmap do MAT Refinado 0.1`.
- [ ] **Commit 02 — base preservada:** `chore: estabelece baseline do Observatorio Refinado 0.1`.
- [ ] **Commit 03 — prova no XAMPP:** `test: valida baseline e banco MySQL da versao refinada`.
- [ ] **Commit 04 — paridade inicial:** `feat(legacy): restaura importador e triagem cientifica do Lab`.
- [ ] **Commit 05 — curadoria:** `feat(data): implementa validacao da base piloto`.
- [ ] **Commit 06 — método:** `feat(temporis): consolida rubrica e calculo versionado`.
- [ ] **Commits posteriores:** uma alteração testável por funcionalidade; atualizar o caderno técnico antes de mudar fórmula, esquema ou critérios.

> **Não usar número de commit como sinônimo de marco:** um marco pode exigir vários commits pequenos. `0.1.0` é o rótulo da base refinada, não uma promessa de que M1–M9 já estejam implementados.

## Critério de pronto para qualquer marco

1. Código versionado, identificado e sem segredo/dump privado.
2. Teste reproduzível com resultado esperado; para banco, incluir teste de escrita e restauração no XAMPP.
3. Feature antiga equivalente **não regrediu**; se houver substituição, a mudança foi aprovada e registrada na matriz.
4. Dados e fontes têm proveniência; exemplos e candidatos não são apresentados como dados científicos aprovados.
5. README, roadmap e caderno técnico atualizados na **mesma alteração**.
6. Nenhuma afirmação de previsão ou acurácia sem resultado aferido em dados não usados para ajuste.

## Quem faz o quê e como retomamos

- **Pesquisador:** valida critérios históricos, aprova escopo e pontuações, disponibiliza o resultado dos testes locais e decide sobre mudanças de objetivo.
- **Assistência técnica:** analisa arquivos e código, propõe ajustes, monta testes e documentação, mas não presume acesso ao MySQL local nem ao GitHub sem conexão.
- **Em uma conversa futura:** começar por `docs/02_CADERNO_TECNICO.md` → seção «Estado e retomada»; consultar este roadmap; verificar `git status`, branch ativa e changelog antes de qualquer alteração.

**Registro editorial:** este documento é deliberadamente geral. Contratos, escalas, esquema, matriz de funcionalidades e protocolo experimental pertencem ao caderno técnico.


### Atualização corretiva (0.1.1 · 28/10/2026)

- [x] Corrigida a dependência da ordem dos marcos em `K_base`.
- [x] Impedido truncamento silencioso de notas das sete rubricas.
- [x] Criadas regressões para datas a.C., eventos futuros e valores inválidos.
- [ ] **M4 permanece parcial**: falta comparar formalmente os métodos A e B,
  fixar escalas, pesos e parâmetros no protocolo científico.
- [ ] **M1 permanece pendente**: paridade funcional com o Têmporis Lab antigo.
- [ ] Validação PHP+MySQL no XAMPP e testes de concorrência permanecem pendentes.

Relatório detalhado: `docs/CORRECOES_0_1_1.md`.
