# Protocolo científico para validar a base da Têmporis

O objetivo é medir se a seleção de marcos e suas 11 rubricas é **reprodutível**, e não somente ampliar a quantidade de linhas no banco.

## Etapas por registro

1. **Identificação**: conservar título/trecho de origem, identificador estável, página, versão bibliográfica, origem do dado e ano original. Se a passagem é ambígua, marcar como pendente sem inventar um acontecimento.
2. **Datação**: registrar qual acontecimento exatamente a data representa (invenção, primeiro uso, difusão, regulamentação); preferir intervalo documentado quando não há consenso. A data extrema, mesmo aceita tecnicamente, deve indicar incerteza.
3. **Duas fontes quando possível**: priorizar evidência primária ou historiografia especializada e segunda fonte independente. Registrar URL, DOI/ISBN, autor, edição, página/trecho, data de acesso e a afirmação que a fonte efetivamente sustenta.
4. **Duplicatas**: comparar se dois candidatos se referem ao mesmo feito ou a fases distintas; justificar fusão, manutenção ou rejeição; preservar ambos os originais.
5. **Rubricas**: avaliar impacto (4), confiabilidade (4), relevância fundacional (3) na escala 0–3; descrever evidência concreta por nota. Falta de evidência fica como **não avaliado**, jamais automaticamente 0.
6. **Revisão e decisão**: revisor humano assina parecer e aprova individualmente; homologação é ação separada e auditável; correção posterior reabre o dossiê.

## Como acelerar sem comprometer o rigor

- Agrupar a fila por `próxima ação` e tipo de bloqueio; trabalhar em lotes *operacionais* para buscar fontes ou completar datas, **não homologar em lote**.
- Criar pesquisas por obra/páginas próximas: uma sessão de leitura pode documentar várias entradas sem copiar justificativas genéricas.
- Reutilizar uma referência bibliográfica legítima entre vários eventos só quando as passagens correspondentes foram de fato conferidas; guardar número de página individual.
- Congelar versões com SHA-256 do banco exportado e registrar quem avaliou, quando e por qual protocolo.

## Validação que ainda exige dados reais

1. **Concordância interavaliadores**: sortear candidatos representativos de diferentes períodos e regiões; dois revisores pontuam independentemente. Executar `cli/concordancia_independente.php` e investigar notas divergentes. Kappa não implica validade documental e pode ser indefinido em amostras uniformes.
2. **Sensibilidade**: variar os pesos das 11 rubricas, `β`, resolução da data e retirar um marco por vez (leave-one-out). Relatar magnitude das mudanças e marcos influentes.
3. **Robustez de amostragem**: comparar períodos antigos/modernos, regiões, critérios de inclusão e fontes primárias/secundárias. O conhecimento histórico disponível aumenta no presente e pode produzir *aparente aceleração* por viés de observação.
4. **Teste de hipótese**: usar taxa de crescimento/inclinações por janelas pré-definidas da série, contrastando modelos linear, exponencial e logístico com R², RMSE, análise de resíduos e, principalmente, dados fora da amostra.
5. **Validação externa**: confrontar resultados com séries de historiadores e literatura sobre mudanças tecnológicas (incluindo Modis/Huebner quando metodologicamente comparáveis), explicitando incompatibilidades de definições.

## Critério para dizer que um candidato está validado

Um marco só está pronto para a série homologada quando há identificação histórica verificável, data coerente ou intervalo documentado, fontes cadastradas e realmente vinculadas, 11 rubricas com justificativas, pendências justificadas individualmente, revisão humana registrada e ato de homologação separado. Ainda assim, **homologado** não é sinônimo de verdade histórica definitivamente estabelecida ou confirmação de aceleração.
