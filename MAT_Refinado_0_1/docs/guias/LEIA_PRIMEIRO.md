> **ATUALIZAÇÃO IMPORTANTE — Têmporis reorganizado (09/11/2026):** para a navegação atual, seleção de dados e diagnóstico do MySQL, leia primeiro [LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md](LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md). Este documento descreve uma etapa anterior do projeto.

# MAT — Atualização da navegação e das rodadas (v1)

**Versão adaptada a partir do ZIP `MAT_Refinado_Git(1).zip` enviado nesta conversa.**
Este pacote atualiza **somente** o Observatório refinado localizado dentro de `MAT_Refinado_0_1/`. O laboratório antigo, que está na raiz `MAT_Refinado_Git/`, não foi modificado.

## Instalar no XAMPP (sem mexer nos bancos)

1. **Faça uma cópia de segurança da pasta atual `MAT_Refinado_0_1`**; guarde com um nome como `MAT_Refinado_0_1_BACKUP_MENU`.
2. Extraia este ZIP em qualquer pasta do Windows.
3. Entre em `ARQUIVOS_PARA_COPIAR` e copie **todo o conteúdo dessa pasta** (arquivos `index.php` e pastas `app`, `assets`, `tests` e `views`) para dentro da pasta atual `MAT_Refinado_0_1`.
4. Quando o Windows perguntar, clique para **substituir os arquivos de mesmo nome**. Os arquivos novos serão adicionados automaticamente.
5. Inicie Apache e MySQL no XAMPP. Abra o MAT da forma que costuma abrir. Se ele está em `C:\xampp\htdocs\MAT_Refinado_Git\MAT_Refinado_0_1`, use `http://localhost/MAT_Refinado_Git/MAT_Refinado_0_1/`.
6. Entre com o seu usuário pesquisador. Abra **Pesquisa e curadoria → Rodadas de pesquisa**.

Se o MAT estiver diretamente em `C:\xampp\htdocs\MAT_Refinado_0_1`, copie para **essa** pasta e abra `http://localhost/MAT_Refinado_0_1/`.

**Não abra o ZIP no phpMyAdmin.** Esta é uma atualização de **arquivos PHP/CSS/JS**, não um arquivo SQL. A atualização não cria tabelas, não modifica seus registros e não muda a Têmporis.

## Dados das rodadas

As tabelas da pesquisa, importadas anteriormente, aparecem na sua captura dentro do banco **`mat_v5`**. A nova tela consulta esse banco separadamente do banco do Observatório (`mat_observatorio`).

Por padrão a tela usa `mat_v5` e as **mesmas credenciais de conexão** que já estão no `config.local.php` do Observatório. Se a pesquisa tiver sido importada em outro banco, acrescente à lista de configuração de `config.local.php`:

```php
'curadoria_db_name' => 'NOME_DO_BANCO_ONDE_ESTAO_AS_TABELAS',
```

Não publique `config.local.php` no GitHub. Se a tela disser "Base auxiliar ainda não disponível", verifique primeiro: MySQL iniciado, as cinco tabelas `temporis_cur_*` existentes no banco indicado, e permissões do usuário MySQL nesse banco. Para ver a tela **Rodadas de pesquisa**, é necessário estar logado como pesquisador.

## Como verificar

Abra o menu em diferentes páginas (Acervo, Têmporis, Laboratório, Rodadas). Ele deve abrir automaticamente a seção que contém a página selecionada, sem criar abas duplicadas. No celular, use o botão ☰ e feche com × ou Escape.

Se tiver PHP no terminal, entre em `MAT_Refinado_0_1` e execute:

```bat
C:\xampp\php\php.exe tests\test_navegacao_curadoria.php
C:\xampp\php\php.exe tests\test_menu_inicial.php
C:\xampp\php\php.exe tests\test_core.php
```

## Voltar à versão anterior

Restaure a pasta que você copiou no passo 1. Como a atualização não altera banco de dados, não é necessário executar nenhum SQL de reversão.

## Depois de aprovar

Você pode subir as mudanças ao GitHub numa branch separada, **depois de testar no seu próprio XAMPP**. Este pacote não faz commit e não publica nada automaticamente.
