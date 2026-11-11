# Inspeção do MAT e alterações aplicadas

**Fonte examinada:** `MAT_Refinado_0_1(2).zip`, com PHP, classes `CuradoriaRevisao`, `CuradoriaFilaRapida`, `CuradoriaRodadas`, `TemporisCuradoria`, vistas e testes.

**SQL recebido:** `cur_log(1).sql`, arquivo exportado às 19h14 de 05/11/2026. Contém somente `cur_log`; não apresenta linhas atuais de `cur_candidatos`, `cur_criterios` nem `cur_fontes`. Por isso, não foi possível executar testes contra o banco atual do usuário.

## Origem do bloqueio

Antes, `CuradoriaRevisao::impedimentos()` reunia automaticamente: data não validada, ausência de fonte do intervalo, pendência histórica, duplicata possível, fonte documental ausente e onze rubricas com justificativas e vínculos individuais. Uma pendência histórica genérica mantinha o bloqueio mesmo quando as 11 notas já existiam.

## Política agora implementada

- **Simplificado:** validação estrutural mínima para **aprovação preliminar**: evento identificável, um ano de referência (pode ser extraído, sem ser declarado validado), pelo menos uma fonte cadastrada, e notas de 1 a 3 nos 11 critérios. Pendências, duplicatas e pesquisa de fonte por critério viram avisos, não impedimentos preliminares.
- **Rigoroso:** regras anteriores intactas, acrescidas do bloqueio explícito de nota `PROVISORIA_SEM_EVIDENCIA`.
- **Homologar:** sempre avalia o rigoroso, independentemente do seletor da interface.
- **Série oficial:** sempre avalia o rigoroso. O modo simplificado não transforma avaliação exploratória em série oficial.
- **Preencher nota 1:** ação humana individual ou em lote de até 25 marcos; exige fonte geral, login, CSRF, justificativa e confirmação. Grava situação provisória explícita e log, sem apagar pendências ou aprovar automaticamente.

## Limitações e próximos passos

1. O conteúdo científico das 800 fichas não foi verificado por completo; não foi possível provar quais fichas passarão a ficar aptas antes de testar no banco atual.
2. Notas 1 provisórias não significam impacto realmente baixo: essa convenção pode enviesar o índice e exige análise de sensibilidade.
3. O código recebeu **verificação de sintaxe de 155 arquivos PHP** e **36 arquivos de testes executados com sucesso**, além de um novo teste para o modo simplificado. Um teste pré-existente (`test_temporis_graficos_2026.php`) falha também no ZIP original, em uma asserção textual sobre fórmula e escala legada.
4. Uma integração real com MariaDB/XAMPP ainda deve ser executada pelo usuário, após backup.
