> **ATUALIZAÇÃO IMPORTANTE — Têmporis reorganizado (09/11/2026):** para a navegação atual, seleção de dados e diagnóstico do MySQL, leia primeiro [LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md](LEIA_PRIMEIRO_TEMPORIS_REORGANIZADO.md). Este documento descreve uma etapa anterior do projeto.

# MAT Refinado + MAT Final | instalação sem perda de dados

Este projeto une o **MAT_Refinado_0_1** e o laboratório experimental **MAT Final** numa **única pasta**, sem substituir os bancos de dados de trabalho. O MAT Final usa uma cópia histórica da curadoria, exportada em **07/11/2026**; suas conclusões são exploratórias.

## Instalação do site (Windows / XAMPP)

1. Faça **backup da pasta e dos bancos MySQL** existentes. Não exclua a pasta atual.
2. Extraia este ZIP. Copie a pasta `MAT_Refinado_0_1` para `C:\xampp\htdocs\`, **ao lado** da pasta antiga, se for preservar a instalação atual (nesse caso use outro nome de diretório). Se substituir arquivos de uma pasta existente, faça backup antes.
3. Inicie **Apache** e **MySQL** no XAMPP. Abra `http://localhost/MAT_Refinado_0_1/index.php`.
4. A navegação/curadoria do Refinado continua usando `mat_observatorio` e `mat_temporis_curadoria` (ou os nomes configurados no `config.local.php`). **Não execute esquemas nem SQL de criação do Refinado em bancos já povoados sem auditoria/backup.**

## Abrir o método novo imediatamente — sem MySQL adicional

Agora o Têmporis novo abre diretamente em **`http://localhost/MAT_Refinado_0_1/index.php?page=temporis`**.

O ZIP inclui um snapshot JSON **real dos dados do MAT Final**, exportado em 07/11/2026 (800 candidatos; 667 elegíveis no recorte amplo). Se o banco adicional ainda não existir, os gráficos, filtros, projeções e retrotestes usam automaticamente esses dados estáticos. Isso não corresponde a uma atualização ao vivo nem à validação das pontuações.

O menu aponta primeiro para **Índice Têmporis → MÉTODO ATUAL · MAT Final**. Para ver os métodos anteriores, acesse `?page=temporis_reformulado` (logaritmo) e `?page=temporis_legado` (potência); o comparativo está em `?page=metodo`.

## Opcional: usar um banco MySQL isolado em vez do snapshot estático

O módulo novo tem banco próprio: **`mat_final_curadoria`**. Isso impede que o snapshot substitua sua curadoria em andamento.

**Opção A — instalador automatizado (base nova)**

Abra PowerShell na pasta extraída e execute:

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALAR_SNAPSHOT_MAT_FINAL.ps1
```

Para outro XAMPP:

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALAR_SNAPSHOT_MAT_FINAL.ps1 -XamppDir "D:\Faculdade nero\xampp"
```

Caso o MySQL tenha senha, use `-DbPassword "SUA_SENHA"` no comando (evite deixar credenciais em histórico ou screenshots). O script **recusa a importação se `mat_final_curadoria` já existir**, e não altera `mat_temporis_curadoria` nem `mat_observatorio`.

**Opção B — phpMyAdmin (base nova)**

1. Crie um banco **vazio** chamado `mat_final_curadoria`, com collation `utf8mb4_unicode_ci`.
2. Selecione **somente esse banco** no phpMyAdmin e importe `instalacao/mat_final_curadoria_snapshot.sql` (arquivo de aproximadamente 13 MB descompactado). Se o upload exceder o limite do phpMyAdmin, use o instalador PowerShell ou ajuste `upload_max_filesize` / `post_max_size` no PHP do XAMPP.
3. Não importe o snapshot no banco original `mat_temporis_curadoria`. O dump é destinado a um **banco novo**.

Após importar, abra `http://localhost/MAT_Refinado_0_1/index.php?page=temporis`. No menu: **Índice Têmporis → MÉTODO ATUAL · MAT Final**. Verifique com:

```powershell
C:\xampp\php\php.exe tests\test_temporis_final.php
C:\xampp\php\php.exe tests\test_metodo_tres_versoes.php
C:\xampp\php\php.exe tests\verificar_bancos_mat_final.php
```

## Bancos de dados / segurança

| Função | Nome padrão | É modificado pela integração? |
|---|---|---|
| Acervo do MAT Refinado | `mat_observatorio` | **Não** |
| Curadoria original do MAT Refinado | `mat_temporis_curadoria` | **Não** |
| Snapshot exploratório do MAT Final | `mat_final_curadoria` | **Criado apenas se ausente, mediante importação** |

O laboratório faz apenas consultas SQL (`SELECT`); o próprio snapshot não é automaticamente sincronizado com a curadoria original. Edite `config.local.php` **somente** caso precise apontar para bancos já criados sob outros nomes. Não aponte `temporis_final_db_name` para o banco original: o código bloqueia esse caso.

Não foi importada nenhuma nota nem evidência automaticamente para a curadoria original. Datas extraídas, marcas `SEM_EVIDENCIA` e pontuações provisórias são mostradas como limitações. O valor **T(2026) ≈ 156,24** se refere exclusivamente à exportação e configuração padrão do MAT Final de 07/11/2026; não é dado validado externamente nem permanece fixo se o banco mudar.

## O que entrou na junção

- Motor `app/TemporisFinal.php`, página `views/temporis_final.php`, CSS `assets/mat-final.css`.
- Caderno comparativo de método (`views/metodo.php` e `assets/metodo-tres-versoes.css`).
- Nova metodologia como **rota e destaque principais** no menu e na home; a reformulada anterior permanece em rota própria; notas de contexto nas páginas de comparação e projeções.
- Snapshot JSON local de leitura, gerado dos registros do SQL exportado (não são dados fictícios); sem dependência de MySQL isolado para visualizar o laboratório.
- Snapshot SQL histórico, testes aritméticos e validador opcional de banco.
- Preservados os arquivos de curadoria, o SQL, as configurações e a estrutura das páginas do MAT Refinado, inclusive MAT-Proj.

**Limitação de testes:** a verificação sintática PHP e os testes unitários podem ser executados sem MySQL. Importação real, conexão com XAMPP e fluxo com dados devem ser conferidos no Windows após o passo de ativação.
