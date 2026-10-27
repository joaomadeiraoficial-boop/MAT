# MAT — Base piloto de 17 marcos com rastreabilidade documental

**Versão:** 2026-10-26  
**Escopo:** pequeno conjunto inicial, deliberadamente **não representativo** da história completa da tecnologia.  
**Compatibilidade:** MAT Observatório v6.2 (`database/schema.sql`).

## O que esta base faz — e não faz

Inclui 17 acontecimentos com datas/descrições verificáveis em referências institucionais ou artigo de revisão. Cada registro possui: título específico, ano (negativo para a.C.), categoria, resumo original curto, fonte identificada, URL e notas para evitar anacronismos. O script SQL inclui automaticamente referência em `fontes` e vínculo em `marco_fontes`.

**Não faz**: provar aceleração tecnológica, demonstrar completude de fontes, validar importância relativa, gerar notas automáticas, antecipar o resultado da Têmporis ou resolver viés de seleção. `status='pendente'`, `incluir_temporis=0`, `impacto=NULL`, `confiabilidade=NULL` e `fator_fundacional=NULL` são propositais.

## Como instalar em mat_observatorio pelo phpMyAdmin

1. **Exporte antes** um backup SQL do banco `mat_observatorio`.
2. Instale o MAT Observatório **v6.2** com `database/schema.sql` se ainda não tiver instalado. Se a instalação já existir, use o procedimento de atualização correspondente, não crie as tabelas novamente.
3. No phpMyAdmin, entre no banco `mat_observatorio` → **Importar** → selecione `base_piloto_17_marcos.sql` → Executar.
4. Entre no **Acervo** e no **Laboratório Científico** para encontrar os registros pendentes, com bibliografia vinculada.
5. As consultas SQL de verificação estão no fim do arquivo. Espera-se 17 entradas **novas em banco vazio**; se houver registros de mesmo título/ano, o script evita duplicá-los.
6. **Não** execute `database/demo.sql` se deseja um acervo sem marcos ilustrativos. Se já importou `demo.sql`, filtre ou mantenha seus registros no status `demonstracao`; não os inclua em análises científicas.

**Reexecução:** usa `NOT EXISTS` para evitar duplicação de fonte por URL e de marco por título+ano. Não altera as pontuações existentes. Os registros são marcados `base_piloto_17` no campo `etiquetas` somente quando inseridos originalmente; marque registros já presentes manualmente se necessário.

## Arquivos

- `base_piloto_17_marcos.sql`: caminho **recomendado**, gera registros e vínculos no MySQL com notas e URLs.
- `marcos_17_fontes.csv`: alternativa para **Importar cronologias**; a importação da aplicação cria itens brutos em revisão e, ao promovê-los, **não** recria automaticamente os vínculos completos ou todos os metadados. Portanto, prefira o SQL.
- `marcos_17_catalogo.json`: catálogo auditável para estudo e evolução do pipeline.

## Protocolo de qualidade antes da Têmporis

1. Definir os critérios de elegibilidade **antes** de decidir quais marcos serão aprovados.
2. Registrar ao menos uma fonte de confirmação independente para marcos com discussão historiográfica.
3. Distinguir **invenção**, **primeira demonstração**, **introdução comercial**, **descoberta** e **adoção em escala**.
4. Para datas aproximadas, registrar incerteza e, quando aplicável, um intervalo. A coluna `ano` serve à ordenação e não implica uma data precisa.
5. Aplicar a rubrica de impacto, confiabilidade e fator fundacional em avaliação separada; manter valores nulos enquanto isso não acontecer.
6. Testar duplicatas e vieses regionais/temporais. Os 17 marcos são escolhidos intencionalmente, com forte cobertura de tecnologias modernas; não constituem amostra aleatória.
7. Testar sensibilidade do índice quando houver amostra mais ampla ou fontes comparativas independentes.

## Referências por registro

| Ano | Marco | Referência | Alerta de interpretação |
|---|---|---|---|
| 3300 a.C. | Registros protocuneiformes na Mesopotâmia | [The Metropolitan Museum of Art](https://www.metmuseum.org/art/collection/search/327385) | Marco situado aproximadamente em 3300 a.C.; não afirmar ser a primeira escrita em todos os lugares. |
| 1377 | Impressão do Jikji com tipos metálicos móveis | [Bibliothèque nationale de France](https://www.bnf.fr/en/jikji-treasure-world-printing) | Não é necessariamente a invenção inicial dos tipos móveis; é o exemplar preservado e documentado em questão. |
| 1455 | Impressão da Bíblia de Gutenberg em Mainz | [Library of Congress](https://www.loc.gov/exhibits/bibles/the-gutenberg-bible.html) | Não descrever como primeiro livro impresso do mundo; houve impressão metálica móvel anterior na Coreia. |
| 1712 | Máquina atmosférica de Thomas Newcomen | [Science Museum Group](https://collection.sciencemuseumgroup.org.uk/objects/co2000/demainbrays-newcomen-engine-model) | A máquina de Newcomen não deve ser confundida com os aprimoramentos posteriores de James Watt. |
| 1796 | Primeira vacinação contra a varíola por Edward Jenner | [Organização Mundial da Saúde (OMS)](https://www.who.int/news-room/spotlight/history-of-vaccination/a-brief-history-of-vaccination) | Práticas anteriores de variolização existiam e devem ser diferenciadas da vacinação de Jenner. |
| 1844 | Demonstração pública do telégrafo elétrico de Morse | [Library of Congress](https://www.loc.gov/item/mcc.019/) | Marco de demonstração pública do sistema, não a origem de toda comunicação elétrica. |
| 1876 | Transmissão de fala inteligível pelo telefone de Bell | [Smithsonian Institution](https://avpreservation.si.edu/spotlight/phones) | Marco de demonstração de Bell; a história da invenção da telefonia envolve outras contribuições. |
| 1903 | Voos controlados e motorizados dos irmãos Wright | [Smithsonian Institution](https://www.si.edu/object/nasm_A19610048000) | A descrição deve manter as condições técnicas: controle, propulsão e sustentação; não apenas primeiro objeto a voar. |
| 1928 | Descoberta da penicilina por Alexander Fleming | [American Chemical Society](https://www.acs.org/education/whatischemistry/landmarks/flemingpenicillin.html) | Não confundir a descoberta de 1928 com o desenvolvimento terapêutico posterior por Florey, Chain e colaboradores. |
| 1946 | Apresentação pública do computador ENIAC | [Computer History Museum](https://www.computerhistory.org/timeline/1946/) | Não reivindicar que foi o primeiro computador de qualquer tipo; existem definições e predecessores distintos. |
| 1947 | Demonstração do transistor de contato pontual | [Computer History Museum](https://www.computerhistory.org/revolution/story/273) | Diferenciar transistor de contato pontual (1947) de transistor de junção (posterior). |
| 1958 | Demonstração de circuito integrado por Jack Kilby | [Smithsonian Institution](https://www.si.edu/object/nmah_689592) | Marco demonstrativo de Kilby; não atribuir exclusivamente a ele todas as arquiteturas de circuitos integrados modernos. |
| 1969 | Operação inicial da rede ARPANET com quatro nós | [DARPA](https://www.darpa.mil/news/features/arpanet) | ARPANET não é sinônimo de World Wide Web; distinguir rede e aplicação Web. |
| 1971 | Lançamento comercial do microprocessador Intel 4004 | [Smithsonian Institution](https://www.si.edu/object/intel-4004-microprocessor%3Anmah_713495) | Especificar lançamento comercial do Intel 4004, evitando generalização sobre toda a história do processador. |
| 1989 | Proposta inicial da World Wide Web no CERN | [CERN](https://home.cern/science/computing/the-birth-of-the-web/short-history-web/) | O primeiro navegador e servidor surgiram em 1990; não confundir proposta com disponibilização pública posterior. |
| 2003 | Conclusão anunciada do Projeto Genoma Humano | [National Human Genome Research Institute (NIH)](https://www.genome.gov/about-genomics/educational-resources/fact-sheets/human-genome-project) | Não afirmar que a sequência de 2003 não continha lacunas; maior completude veio em trabalhos posteriores. |
| 2012 | Demonstração do sistema CRISPR-Cas9 programável | [Nature Reviews Molecular Cell Biology](https://www.nature.com/articles/s41580-020-00307-9) | Marco metodológico de 2012; os sistemas naturais CRISPR e estudos anteriores são distintos desse protocolo. |

## Próximo commit sugerido

```text
feat(data): adiciona base piloto documental de 17 marcos para revisao
```

**Importante:** não publicar dumps privados do MySQL no Git. Os CSV/SQL/JSON aqui incluem somente dados curados e links de fontes; nenhuma imagem/texto longo de terceiros foi reproduzido.
