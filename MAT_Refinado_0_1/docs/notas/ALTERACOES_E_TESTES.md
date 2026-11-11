# Alterações técnicas — MAT / Navegação agrupada

## Arquivos

- `index.php`: rota autenticada `rodadas`; destaque correto de Novo marco; retorno ao login para a rota original.
- `views/login.php` e `actions.php`: retorno seguro para Rodadas ou Andamento após login, sem permitir URLs de redirecionamento arbitrárias.
- `views/header.php`: reorganização da navegação em O Observatório, Exploração histórica, Índice Têmporis e Pesquisa e curadoria; mantém as rotas e visuais originais, inclusive extensões V07; breadcrumbs por área e destaque de página ativa.
- `assets/navegacao-grupos.css`: estilos dos novos submenus usando as variáveis e a tipografia originais do MAT.
- `assets/app.js`: acionamento dos grupos e navegação móvel acessível, escape e fechamento do painel, sem alterar os demais scripts do projeto.
- `app/CuradoriaRodadas.php`: conexão independente, somente consultas SELECT ao banco auxiliar `mat_v5`, paginação, filtros por estado, fichas de critérios e fontes, validação de URLs.
- `views/rodadas.php`: painel documental com indicadores dinâmicos, Rodadas 1 e 2, lista dos candidatos e ficha com 11 critérios/justificativas/evidências.
- `assets/rodadas.css`: página editorial integrada à identidade original.
- `tests/test_navegacao_curadoria.php`: regressões de rotas, segurança e integridade do menu.

## Integridade científica

Sem `CREATE`, `DROP`, `INSERT`, `UPDATE` ou `DELETE` no banco; a curadoria fica separada da série homologada. Sem alteração de fórmulas, pesos, rubricas ou estado da Têmporis. O PHP não transfere candidatos do banco `mat_v5` para o acervo `mat_observatorio`.

## Testes realizados em ambiente isolado

- Sintaxe PHP: arquivos modificados passam por `php -l`.
- Node.js: JavaScript validado com `node --check`.
- Testes da versão original: `test_core.php`, `test_science.php`, `test_regressoes_0_1_1.php`, `test_menu_inicial.php`, `test_integracao_pagina_backtest.php`, `test_navegacao_mat_proj.php` — todos com exit 0.
- Novo teste `test_navegacao_curadoria.php`: aprova 17 verificações.
- Renderização com Chromium/Playwright por HTML estático: desktop 1440px e mobile 390px, grupo ativo, expansão exclusiva, abertura/fechamento do menu móvel, Escape, sem erros de JavaScript e sem rolagem horizontal.

**Limitação de teste:** o ambiente de teste não tem extensão `pdo_mysql` nem a base real do XAMPP. Assim, não foi possível confirmar a consulta com os dados vivos de `mat_v5`; a interface de ausência de conexão foi verificada e a camada de consulta foi validada estaticamente. A consulta com os dados reais precisa ser conferida no seu computador antes de um commit no GitHub.

**Nota visual:** as capturas incluem a faixa de MySQL desconectado por serem do ambiente de desenvolvimento; quando o MySQL do usuário estiver conectado, o MAT exibirá seus próprios dados.
