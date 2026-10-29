# MAT-Proj — acesso pela página inicial

## Correção de integração (MAT-Proj 0.1.1)

O MAT-Proj 0.1 executava pelo endereço `mat_proj/`, mas não havia acesso pela página inicial do MAT Refinado.

### Arquivos alterados

- `views/header.php`: adiciona **MAT-Proj · Projeções** no menu lateral da estação de trabalho.
- `views/observatorio.php`: adiciona o mesmo acesso nos botões de abertura do Observatório.
- `docs/03_MAT_PROJ_ETAPA_01.md` e `mat_proj/README.md`: atualizam o estado documentado da navegação.
- `tests/test_navegacao_mat_proj.php`: testa a presença do acesso e a existência dos módulos.

Nenhum cálculo da Têmporis, parâmetro do MAT-Proj, tabela ou registro de banco foi alterado.

### Instalação conservadora no Windows

1. Faça o commit ou backup de eventuais alterações locais em `views/header.php` e `views/observatorio.php` antes de extrair este pacote.
2. Coloque o ZIP `MAT_Proj_0_1_1_ACESSO_INICIAL.zip` em `D:\Faculdade nero\xampp\htdocs`.
3. Abra o PowerShell no repositório:

```powershell
cd "D:\Faculdade nero\xampp\htdocs\MAT_Refinado_Git"
git status --short
Expand-Archive -LiteralPath "..\MAT_Proj_0_1_1_ACESSO_INICIAL.zip" -DestinationPath "." -Force
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\tests\test_navegacao_mat_proj.php
& "D:\Faculdade nero\xampp\php\php.exe" .\MAT_Refinado_0_1\mat_proj\tests\test_engine.php
```

4. Com o Apache ligado, acesse:

`http://localhost/MAT_Refinado_Git/MAT_Refinado_0_1/`

O menu **MAT-Proj · Projeções** e o botão da abertura devem abrir o laboratório. Caso haja cache, use `Ctrl + F5`.

5. Depois de conferir, registre somente estes arquivos e envie à branch correta.

### Validação local do pacote

- Sintaxe PHP das duas views: OK.
- Teste de integração da navegação: OK.
- Testes do MAT-Proj: 9/9 OK.
- Requisições HTTP à homepage e ao laboratório: HTTP 200 em servidor PHP local de demonstração.

O servidor usado para a verificação não possuía driver PDO MySQL; a ligação ao banco real permanece pendente de verificação no XAMPP do usuário.
