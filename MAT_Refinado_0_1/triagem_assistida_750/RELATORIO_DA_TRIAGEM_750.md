# MAT / Índice Têmporis — Triagem documental de 750 dossiês

**Base:** exportação de `mat_temporis_curadoria.sql`, enviada em 05/11/2026.  
**Código comparado:** MAT Refinado 0.1.2.  
**Método:** processamento local, somente leitura, das tabelas e das justificativas existentes; agrupamento administrativo e conferência pontual com o livro McClellan & Dorn (2006) e páginas institucionais.  
**Escopo de confiança:** classificação em lote **não é** aprovação histórica, conferência de cada URL, pontuação autônoma ou homologação científica.

## 1. Inventário integral do banco

| Indicador | Resultado |
| --- | ---: |
| Candidatos cadastrados | **750** |
| Rubricas existentes (`cur_criterios`) | **8.250**, exatamente 11 por candidato |
| Rubricas com nota de 1 a 3 | **2.300** |
| Rubricas sem nota (`SEM_EVIDENCIA`) | **5.950** |
| Candidatos com 11 rubricas pontuadas | **110** |
| Candidatos incompletos | **640** |
| Candidatos com ano validado registrado | **196** |
| Sem ano validado (`NULL`) | **554** |
| Fontes cadastradas (`cur_fontes`) | **1.114** |
| Candidatos com fonte externa catalogada | **440** |
| Candidatos com URL externa no registro | **316** |
| Candidatos com pendências textuais | **743** |
| Candidatos com ligações potenciais de duplicata | **204** |
| Rodadas | **75** |
| Registros de log | **916** |
| Estados humanos | **582 NAO_REVISADO, 165 EM_REVISAO, 2 REJEITADO, 1 APROVADO** |
| Homologados | **0** |

A contagem do dump é ligeiramente diferente da captura de tela anterior (por exemplo, 640 incompletos contra 638); use este retrato como referência, sem presumir que o MySQL continue idêntico após a exportação.

### Verificações estruturais automatizadas

- Todos os 750 registros contêm os 11 códigos esperados de rubrica. Não houve `fonte_id` ausente entre os vínculos cadastrados dos critérios (a existência no banco **não certifica a qualidade da fonte**).
- As 2.300 notas presentes são 1, 2 ou 3, compatíveis com o domínio de notas da aplicação. Os 5.950 critérios não pontuados permanecem `NULL`/`SEM_EVIDENCIA`; não os converta em nota 1.
- Nos candidatos que têm ambos os campos de paginação, `pagina_pdf - pagina_livro = 13`; os 50 registros externos das rodadas 56–60 não possuem páginas do livro-base, o que é esperado para fontes que não foram retiradas diretamente daquela obra.
- Ausência de 554 datas validadas é uma **pendência de pesquisa** e não prova erro no banco. Não sugerimos preencher datas exatas pela época de nascimento de inventores ou pelo início de um século.
- Não foram detectadas incongruências automáticas de nota fora da faixa ou fonte_id inexistente; isso **não significa que todas as fontes tenham sido acessadas e verificadas**.

## 2. Fila de trabalho obtida do SQL (soma = 750)

| Trilha | Qtd. | Encaminhamento |
| --- | ---: | --- |
| `AUDITAR_PRIORITARIO` | **78** | 11 notas + data + fonte externa catalogada; verificar cada alegação e registrar parecer |
| `COMPLETAR_POUCAS_NOTAS` | **14** | 8–10 rubricas preenchidas, resolver lacunas |
| `CONFERIR_DATA_FONTES` | **31** | 11 notas, mas data ou fontes externas ainda exigem definição |
| `TRIAR_ESCOPO` | **123** | verificar se o assunto é um acontecimento independente, não apenas um período, pessoa ou debate |
| `TRIAR_DUPLICATA` | **18** | decidir se o candidato é efetivamente redundante e vincular à ficha principal |
| `PESQUISAR_NOTAS` | **483** | completar pesquisa e rubricas com `SEM_EVIDENCIA` |
| `DECISAO_EXISTENTE_NAO_ALTERAR` | **3** | conservar duas rejeições e uma aprovação já registrada |

**Importante:** as trilhas são classificações mecânicas, não uma recomendação de exclusão automática nem de relevância histórica. Alguns candidatos classificados como escopo podem ser reformulados em eventos pontuais.

## 3. Conferência documental pontual (não extrapolar para os demais)

1. **MCD-00040 — Sutra do Diamante, 868.** A British Library documenta exemplar de 11 de maio de 868, com impressão por blocos de madeira. O registro do MAT diferencia corretamente exemplar datado de invenção da técnica. Fonte: https://idp.bl.uk/discover/learning/buddhism-on-the-silk-roads/articles/buddhism-on-the-ground/buddhist-texts-the-diamond-sutra/
2. **MCD-00147 — Philosophical Transactions, 1665.** A Royal Society documenta início em março de 1665. Ano pontual apoiado, mas impacto/fundacionalidade ainda demandam auditoria. Fonte: https://www.royalsociety.org/journals/publishing-activities/publishing350/history-philosophical-transactions/
3. **MCD-00007 — Grande Pirâmide, -2570.** Ministério do Turismo e Antiguidades do Egito situa o reinado de Quéops aproximadamente entre 2589 e 2566 a.C. e estima anos de construção; `-2570` é plausível como ponto de uma faixa, **não** comprovado como data exata de conclusão ou início. Fonte: https://egymonuments.gov.eg/monuments/the-great-pyramid/
4. **MCD-00019 — Parafuso de Arquimedes, -234.** O livro-base, PDF p. 100, descreve a máquina como invenção *alegadamente* associada a Arquimedes no século III a.C.; não sustenta exatamente `-234` como data da invenção. Recomenda-se investigar representação por intervalo e manter a atribuição como controversa.

Nenhum desses quatro recebeu aprovação ou alteração automática de `ano_validado` no banco original. `EXEMPLOS_CONFERENCIA_DOCUMENTAL.csv` registra os encaminhamentos propostos e as referências.

## 4. O que foi produzido

- `FILA_COMPLETA_750.csv`: todos os candidatos, com ID, estado original, data, fontes, pendências, rubricas e próxima ação.
- `AUDITORIA_PRIORITARIA.csv`: todos os candidatos nas três trilhas mais próximas da conclusão documental.
- `TRIAGEM_DE_ESCOPO.csv`: os casos que primeiro exigem delimitação ou comparação de duplicatas.
- `PROBLEMAS_DE_INTEGRIDADE.csv`: apontamentos por candidato; neste retrato são as 554 datas pendentes. São **alertas**, não comprovações de erro histórico.
- `INSTALAR_TRIAGEM_ASSISTIDA.sql`: cria **duas novas tabelas auxiliares** e carrega os 750 registros por `INSERT IGNORE`. Não muda nada nas cinco tabelas científicas originais.
- `fila_triagem_assistida.php`: painel separado, restrito ao administrador, com pesquisa, filtros, parecer individual, token CSRF, bloqueio de gravações concorrentes e log auxiliar. Uma revisão assistida não autoriza aprovação/homologação.
- `auditar_dump.py`, `gerar_sql_triagem.py`: scripts para reproduzir o inventário a partir do SQL exportado.
- `MAT_FILA_CURADORIA_750.xlsx` (distribuído separadamente e no pacote final): painel e tabelas filtráveis para acompanhamento offline.

## 5. Instalação sem perda de dados (XAMPP)

1. **Backup completo** de `mat_temporis_curadoria`, `mat_observatorio` e `config.local.php`.
2. Instale a aplicação nova em **diretório de testes separado**; não sobrescreva sua instalação que já está funcionando. A pasta do ZIP final é `MAT_Refinado_0_1` por compatibilidade com links.
3. No phpMyAdmin, selecione o banco `mat_temporis_curadoria`; importe **apenas** `INSTALAR_TRIAGEM_ASSISTIDA.sql`. Não reimporte o dump original enviado aqui (ele contém `CREATE TABLE` e pode conflitar com os seus dados).
4. Abra `http://localhost/MAT_Refinado_0_1/fila_triagem_assistida.php` na sua cópia de testes e faça login como administrador.
5. Faça primeiro uma conferência de teste em um candidato; confira as novas tabelas `cur_triagem_assistida` e `cur_triagem_assistida_log` e certifique-se de que o candidato original não mudou.
6. Examine a aba **Prioridade 78** e registre pareceres curtos e específicos. A aprovação oficial, se justificada, será feita **depois** na fila original do MAT.

**Repetibilidade:** o carregamento usa `INSERT IGNORE` para não reescrever pareceres auxiliares. Quando o dump mudar, rode novamente os scripts para obter um relatório atualizado; uma reimportação de staging pode preservar campos antigos de ID já existente. O painel mostra os dados atuais para comparação e a trilha gerada do snapshot continua identificável pelo hash.

## 6. Limitações, transparência e próximos passos científicos

- Os 750 dossiês foram **auditados automaticamente em estrutura**, mas **não receberam 750 verificações históricas independentes**. Não existe base para declarar que os 747 pendentes foram resolvidos.
- **0 registros científicos aprovados por este processamento**; a aprovação já existente no dump foi preservada.
- Datas antigas incertas precisam de intervalo/cenário de sensibilidade, não só um inteiro `ano_validado`.
- Para confiabilidade científica, realize dupla codificação independente de uma amostra estratificada por era, categoria e grau de incerteza e compare concordância por rubrica.
- Para a tese de aceleração, também será necessário testar robustez temporal, critérios de inclusão e modelos concorrentes com dados homologados.
- Sem uma instância MariaDB/XAMPP com credenciais fornecidas no ambiente, foi possível validar parsing, relações e sintaxe PHP, mas não transações SQL reais nem navegação autenticada. Isso exige teste local antes de uso sobre dados vivos.

**Síntese:** este pacote tira a revisão do formato de uma fila linear de 747 e a transforma em lotes priorizados e registráveis. O volume de pesquisa histórica ainda é real, mas o pesquisador não precisa descobrir manualmente, a cada candidato, qual a primeira pendência.
