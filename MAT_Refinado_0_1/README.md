# MAT · Versão Refinada 0.1
### Observatório do Tempo | Modelagem da Aceleração Tecnológica

> **Base de desenvolvimento com identidade autoral, documentação científica e preservação do legado.** Esta versão reúne o Observatório v6.2.1 e as referências técnicas para restaurar integralmente as funcionalidades do MAT v6 Têmporis Lab, sem falsamente declarar que todas já estão migradas.

## Por onde começar

1. **Visão geral e marcos:** [`docs/01_ROADMAP_GERAL.md`](docs/01_ROADMAP_GERAL.md).
2. **Caderno técnico-mestre e contrato de continuidade:** [`docs/02_CADERNO_TECNICO.md`](docs/02_CADERNO_TECNICO.md).
3. **Situação da base anterior:** [`docs/PARIDADE_E_MIGRACAO.md`](docs/PARIDADE_E_MIGRACAO.md).
4. **Base piloto de 17 candidatos:** [`docs/BASE_CIENTIFICA_PILOTO.md`](docs/BASE_CIENTIFICA_PILOTO.md).
5. **Documentação histórica preservada:** [`docs/historico/`](docs/historico/).

## Estado da versão

**Há código executável** de acervo, histórico, dossiês, atlas, panfletos, laboratório de evidências, fontes, triagem, duplicatas, importação CSV em staging, exportações, autenticação e motor experimental Têmporis. **Ainda faltam recursos antigos importantes**, documentados na matriz LF01–LF25 do caderno técnico. Não descartar o Lab antigo!

**Dados:** pacote contém SQL opcional com 17 **marcos candidatos**, todos pendentes de revisão e **fora** da Têmporis até aprovação. A amostra não comprova aceleração e não produz previsão validada.

**Testes executados no ambiente de construção:** `tests/test_core.php` e `tests/test_science.php` passaram; a sintaxe PHP dos arquivos foi verificada. O ambiente não possui `pdo_mysql`; **gravação em MySQL no XAMPP precisa ser testada localmente**.

## Instalação local (XAMPP)

1. Extraia esta pasta em `C:\xampp\htdocs\MAT_Refinado_0_1`.
2. Inicie Apache e MySQL no XAMPP.
3. No `http://localhost/phpmyadmin`, em uma **instalação nova**, importe `database/schema.sql`, criando o banco independente `mat_observatorio`. **Não execute esquema de instalação sobre banco em uso sem backup.**
4. Copie `config.example.php` para `config.local.php` e configure as credenciais locais, nunca commitando `config.local.php`.
5. Em PowerShell dentro da pasta do MAT:

   ```powershell
   C:\xampp\php\php.exe cli\criar_admin.php "Pesquisador MAT" "seu-email@exemplo.com" "sua-senha-segura"
   C:\xampp\php\php.exe tests\test_core.php
   C:\xampp\php\php.exe tests\test_science.php
   ```

6. Abra `http://localhost/MAT_Refinado_0_1/` e confira login, navegação e leitura do banco. Para carregar os **17 candidatos**, faça backup e importe **somente depois** `database/base_piloto_17_marcos.sql` no banco instalado.
7. `ABRIR_PREVIA.html` abre uma **prévia estática** gerada na base 6.2.1, útil para conhecer o design sem MySQL; não substitui testes funcionais.

O esquema existente da v6.2 é preservado; **nenhuma migration nova foi inventada nesta versão 0.1**. Se você já usa o banco 6.2, não reaplique `schema.sql` indiscriminadamente.

## Estratégia de Git para não perder o antigo

O ZIP do MAT v6 Têmporis Lab contém a referência remota `https://github.com/joaomadeiraoficial-boop/MAT.git`. Confira em seu VS Code antes de executar os comandos. **Esta entrega não foi enviada automaticamente ao GitHub.**

**Na pasta RAIZ do seu repositório Git já existente (não dentro deste novo pacote):**

```powershell
git status
git branch --show-current
# Só troque de branch quando o estado estiver limpo.
git switch main
git pull --ff-only
git switch -c feature/mat-refinado-0-1
```

Copie a pasta inteira `MAT_Refinado_0_1` para a raiz desse repositório, sem `.git` dentro da pasta copiada. Em seguida faça **dois commits distintos** (documentos primeiro, aplicação depois):

```powershell
git add MAT_Refinado_0_1/docs MAT_Refinado_0_1/README.md MAT_Refinado_0_1/VERSION
git commit -m "docs: define escopo e roadmap do MAT Refinado 0.1"
git add MAT_Refinado_0_1
git status
git commit -m "chore: estabelece baseline do Observatorio Refinado 0.1"
git push -u origin feature/mat-refinado-0-1
```

Antes de cada commit, inspecione `git diff --cached --stat` e confirme que nenhum arquivo privado entrou no staging. **Não faça merge na `main` enquanto houver pendências de paridade relevantes.**

## Nome e regras da fase

A marca `MAT — Observatório do Tempo` continua; **Versão Refinada 0.1** é o rótulo do novo ciclo e aparece nos títulos e no rodapé. Não confunda `0.1.0` (ciclo novo) com as versões internas legadas `6.2/6.8`, nem com validação científica do índice.

O desenvolvimento seguinte começa pela **paridade funcional com o Lab antigo**, e não pelo módulo preditivo. Consulte a tabela LF01–LF25 do caderno técnico.

### Atualização corretiva 0.1.1

A série histórica foi corrigida para ordenar datas internamente e calcular `K_base`
apenas com eventos até o ano-base. Notas fracionárias dos sete critérios agora
são rejeitadas. Detalhes e regressões: `docs/CORRECOES_0_1_1.md`.

As fórmulas e escalas originais **não foram alteradas**. Não houve migração de
banco nesta revisão. Esta atualização não constitui validação científica.
