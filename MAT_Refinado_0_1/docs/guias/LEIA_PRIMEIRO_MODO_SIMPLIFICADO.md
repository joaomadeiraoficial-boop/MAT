# MAT — Modo simplificado (avaliação exploratória)

## Instalação

1. **Faça backup da pasta MAT e do banco de dados** no phpMyAdmin.
2. Descompacte este pacote **sobre a pasta `htdocs`** (contém `MAT_Refinado_0_1/` e seus arquivos). **Não apague sua pasta atual.**
3. Atualize a página `index.php?page=fila_rapida` e escolha o modo.
4. O projeto usa o banco existente, **não exige importação SQL** para habilitar o modo simplificado.
5. A versão completa distribuída **não inclui seu `config.local.php` pessoal**. Se for substituir a pasta inteira, preserve esse arquivo do seu projeto atual.

## Regras

- **Simplificado (padrão na sessão web):** requer acontecimento individualizado, uma fonte cadastrada, ano válido **validado, intervalar ou bruto provisório**, e 11 notas de 1 a 3. Pendências e duplicatas são **avisos**, não bloqueios preliminares. Não confirma a verdade do marco.
- **Rigoroso:** preserva todas as exigências anteriores. Apenas este modo pode fundamentar uma homologação e entrada na série oficial.
- **Atribuir notas 1 provisórias:** botão por marco **ou lote de até 25**; somente notas nulas/0; preserva notas 2 e 3. A situação `PROVISORIA_SEM_EVIDENCIA` não equivale a um fato comprovado, e **impede homologação rigorosa** até reavaliação.
- **Todos os dados permanecem no banco**. A aprovação continua dependendo do login, CSRF e confirmação individual. O preenchimento mínimo é registrado em `cur_log`.
- Sem fonte cadastrada, nem mesmo o modo simplificado permite registrar notas mínimas automaticamente.

## Aviso científico

Se a nota 1 for atribuída por falta de informação e não por comprovação de baixo impacto, os valores do índice exploratório tornam-se conservadores e potencialmente enviesados. Para o TCC, documente essa convenção e faça uma **análise de sensibilidade** reestimando os marcos provisórios. Nunca apresente o conjunto como homologado apenas porque tem 11 notas.

## Banco SQL recebido

`cur_log(1).sql` contém apenas `cur_log`. Não existe dump atualizado de `cur_candidatos`, `cur_criterios` e `cur_fontes` nesta entrega. Assim, o novo projeto foi verificado em testes PHP e sintaxe, mas não foi conectado ao seu MySQL real.

**Lotes de 25:** processados em transações por dossiê, com resultado agregado. Se uma ficha falhar, outras podem ter sido atualizadas; consulte `cur_log` para auditar. Não é possível desfazer uma rodada inteira por um único ROLLBACK.
