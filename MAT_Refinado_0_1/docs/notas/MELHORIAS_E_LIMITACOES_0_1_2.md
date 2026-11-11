# MAT — Correções e reforço de curadoria (versão 0.1.2)

**Data:** 05/11/2026. **Origem:** cópia de `MAT_Refinado_0_1(1).zip` fornecida pelo pesquisador. **Objetivo:** correções de integridade da curadoria, agilização da fila, ensaios estatísticos e preservação das decisões humanas.

## O que foi implementado

1. **Datas históricas:** suporte de cálculo experimental entre 5 milhões de anos a.C. e 2100 d.C. (`CuradoriaDatacao`). Datas antigas continuam exigindo justificativa, fonte e indicação de intervalo/incerteza. A ampliação do limite técnico não demonstra precisão arqueológica ou prova a idade de qualquer marco.
2. **Memória de cálculo:** 11 rubricas não ficam completas quando alguma referencia um `fonte_id` que não está cadastrado no dossiê; o peso fica indisponível até correção. A prévia continua distinta da Têmporis homologada.
3. **Pendências/duplicatas:** eliminados os atalhos globais de encerramento; cada item selecionado exige parecer específico com ao menos 40 caracteres, fonte cadastrada, confirmação e verificação do estado atual (hash). Há registros individualizados em `cur_log`. Não se inventam fontes ou decisões.
4. **Fila de curadoria geral:** lista os dossiês de 0 a 11 rubricas, filtra incompletos, permite localizar bloqueios por categoria e apresenta sugestão da próxima ação administrativa. Não sugere notas nem aprova automaticamente.
5. **Reabertura do acervo:** alterações substantivas em um marco do banco principal anteriormente aprovado o devolvem à revisão, retirando-o da inclusão oficial até nova aprovação. Alterações de seus vínculos de fontes também reabrem a revisão, com histórico.
6. **CSV:** linhas com tamanho incorreto ficam inválidas em vez de serem corrigidas silenciosamente; cabeçalhos repetidos e ano zero são rejeitados.
7. **Auditoria automatizada:** recursos de aprovar e rejeitar vários dossiês de uma vez foram desativados. A triagem e o encaminhamento para revisão continuam possíveis, sem decidir evidências em nome do pesquisador. Datação por intervalo é considerada no diagnóstico estrutural.
8. **Exportação de pendências:** arquivo `export_curadoria_pendencias.php`, acessível a administrador, gera relatório CSV das tarefas e impedimentos encontrados; contém proteção básica contra fórmulas executáveis em células de planilha.
9. **Sensibilidade leave-one-out:** a versão reformulada pode medir o impacto de omitir um marco de cada vez no cenário experimental ou homologado, sem editar os dados armazenados.
10. **Dupla avaliação independente:** `cli/concordancia_independente.php` compara dois CSVs preenchidos por revisores independentes, calcula concordância exata, diferença média e kappa quadrático por rubrica, explicitando casos indefinidos. Nenhuma pontuação foi gerada automaticamente.
11. **Esquema para instalação nova:** `database/curadoria_cientifica_schema_novo.sql` define um banco científico **vazio**, separado do acervo principal. Não é migração de banco já utilizado nem contém os 300 candidatos.
12. **Testes de regressão:** `tests/test_melhorias_2026.php` e `tests/test_concordancia_csv.php` adicionados às suítes originais.

- **Higiene de distribuição:** arquivos de backup `*.bak` e `config.local.php` não são redistribuídos no ZIP; mantenha sua configuração local de modo privado.

## Limitações e pontos que ainda precisam de verificação

- Este ambiente não possui `pdo_mysql`, nem o conteúdo do banco `mat_temporis_curadoria` em produção. Não foi possível executar transações reais, permissões com administrador autenticado, cliques no navegador, concorrência entre revisores nem reconciliação de schema existente. Os testes são majoritariamente unitários/de rotas sem banco, e não homologam o sistema de ponta a ponta.
- A amostra de 300 IDs do seed **não equivale** a 300 marcos validados. Sem documentos, trechos, fontes e pareceres, a validação histórica item por item depende de pesquisa humana.
- A Têmporis é uma hipótese metodológica. A subida do acumulado não prova aceleração; é necessário análise de taxa, modelos concorrentes, viés temporal e validação fora da amostra.
- Para instalação sobre um banco já existente, não importe o SQL de instalação como se fosse migration. Faça backup e compare o esquema de tabelas, índices e colunas antes de executar qualquer comando.
- A mudança de estado após edição do acervo principal está no fluxo `actions.php`; alterações diretas no banco ou por scripts antigos não necessariamente acionam essa proteção. Para produção, implemente também política de revisão no banco/serviço único.
- A apresentação/fluxo do 404 para dossiês inexistentes foi ajustada na visão de curadoria, mas a resposta HTTP sob banco e sessão reais ainda precisa de teste.

## Ordem sugerida para homologação local

1. Fazer backup completo dos dois bancos `mat_observatorio` e `mat_temporis_curadoria` **e** de `config.local.php`.
2. Copiar/mesclar os novos arquivos na instalação local, mantendo `config.local.php` e dados próprios. Preferir uma cópia em diretório de testes primeiro.
3. Executar `php -l` nos arquivos PHP e todas as suítes em `tests/test_*.php`.
4. Testar um dossiê real incompleto e outro pronto: cadastrar fonte, registrar 11 justificativas, corrigir data, resolver uma pendência com parecer e fonte, tentar aprovar e depois homologar. Conferir `cur_log`.
5. Testar edição e vinculação de fonte em um marco **de teste** aprovado do banco principal; confirmar que ele se torna pendente e sai da Têmporis.
6. Exportar a fila de pendências, congelar um snapshot e registrar a auditoria científica antes de iniciar revisão de históricos.
7. Fazer dupla codificação independente e análise de estabilidade antes de publicar gráficos conclusivos.

**Documento de apoio:** `GUIA_XAMPP_0_1_2.md` e `PROTOCOLO_CIENTIFICO_CURADORIA_0_1_2.md`.
