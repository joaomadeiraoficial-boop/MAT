# MAT — Correção integrada da fila rápida e melhoria visual

## O que este pacote resolve

- Une o MAT com modo simplificado, a interface visual mais limpa e a correção de salvamento que você enviou.
- Corrige o formulário de data/pendências/duplicatas: agora permite pontuar rubricas que ainda estão sem nota na mesma operação, com nota, justificativa e fonte já cadastrada.
- Após salvar, relê os campos gravados no banco ANTES de mostrar sucesso. Se houver erro, reverte a transação e informa o motivo.
- Se a validação falhar, preserva os campos digitados da ficha e da rubrica individual, sem precisar recomeçar.
- Previne clique duplo no formulário de metadados.
- Na ficha, o painel grande "Preenchimento mínimo provisório — nota 1" agora só aparece se existirem notas NULL/0 e fica recolhido.
- O formulário da nota 1 ocupa a largura correta e usa uma caixa de confirmação compacta.
- Não altera notas 2 e 3 automaticamente. Não aprova nem homologa marcos automaticamente.
- A homologação continua exigindo o modo rigoroso.

## Instalação recomendada — PATCH

1. Faça uma cópia de seguranca da pasta `C:\xampp\htdocs\MAT_Refinado_0_1`.
2. Exporte um backup SQL COMPLETO no phpMyAdmin, com todas as tabelas (não apenas `cur_log`).
3. Feche a página MAT no navegador.
4. Extraia `MAT_SALVAMENTO_E_VISUAL_CORRIGIDOS_PATCH.zip` DIRETAMENTE em `C:\xampp\htdocs\`.
5. Confirme a substituição dos arquivos da pasta `MAT_Refinado_0_1`.
6. Abra `http://localhost/MAT_Refinado_0_1/index.php?page=fila_rapida`, aperte Ctrl+F5 e teste com um dossiê que tenha notas faltantes.

IMPORTANTE: Não extraia o ZIP dentro de `C:\xampp\htdocs\MAT_Refinado_0_1`, pois isso criaria uma pasta duplicada. Extraia em `htdocs`.

O PATCH modifica apenas 8 arquivos do projeto (listados abaixo) e adiciona este manual. Nenhuma migração SQL é necessária.
Se você já instalou separadamente a correção de salvamento em patch/PowerShell, use este ZIP MESCLADO (não execute novamente a correção isolada).

## Conferência após o teste

- Edite uma nota da ficha e clique em "Salvar avaliação desta rubrica".
- O resultado precisa mostrar "NOTA CONFIRMADA NO BANCO". A nota/justificativa precisa aparecer atualizada ao recarregar a página.
- Para data e pendências, marque o item efetivamente verificado, selecione fonte, explique o parecer e clique "Salvar e conferir o que foi gravado".
- Apenas digitar uma justificativa sem marcar item, modificar data ou pontuar rubrica agora retorna "Nada foi alterado" — não uma confirmação falsa.
- Ao ocorrer erro, o formulário reabre mantendo o que foi digitado.
- Se existirem critérios NULL ou nota 0, o bloco compacto "Completar ... notas mínimas provisórias" estará disponível.

## Limites dos testes

Foram executados os testes PHP disponíveis: 37 passaram; 1 teste de gráficos continua falhando exatamente como no ZIP original do projeto. A sintaxe dos arquivos PHP e do CSS foi conferida. O ambiente do ChatGPT não dispõe do seu servidor MariaDB ou do banco completo atual: a persistência em seu Windows precisa ser confirmada no teste acima.

## Arquivos substituídos

- `actions.php`
- `app/CuradoriaNormalizacaoMinima.php`
- `app/CuradoriaRevisao.php`
- `assets/fila-rapida.css`
- `assets/fila-rapida.js`
- `tests/test_salvamento_fila_rapida_2026.php`
- `views/datacao_campos.php`
- `views/fila_rapida.php`


Não alteramos `config.local.php`, credenciais, banco de dados ou estrutura de tabelas.
