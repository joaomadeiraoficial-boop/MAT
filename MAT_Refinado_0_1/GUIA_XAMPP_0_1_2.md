# Guia de instalação e verificação — MAT Refinado 0.1.2 (Windows / XAMPP)

## Atenção aos dados já existentes

Este pacote é uma **cópia completa do código**; não representa uma exportação do seu MySQL. A pasta `database/` contém scripts de instalação, não os dados atuais do seu computador. **Não execute instaladores SQL sobre bancos em uso** antes de comparar o esquema e fazer backups.

### 1. Backups (obrigatórios)

No `http://localhost/phpmyadmin` exporte, em arquivos separados, os bancos `mat_observatorio` e `mat_temporis_curadoria` (quando existirem). Guarde uma cópia de sua pasta atual em `C:\xampp\htdocs` e uma cópia privada de `config.local.php`. Não publique seu arquivo de credenciais no Git.

### 2. Instalar primeiro em paralelo

Extraia o ZIP no computador e copie a pasta `MAT_Refinado_0_1` para uma pasta de testes, como `C:\xampp\htdocs\MAT_Refinado_0_1_TESTE`. Copie **sua configuração local** para esta pasta, sem publicar o arquivo. Acesse:

- `http://localhost/MAT_Refinado_0_1_TESTE/index.php?page=andamento`
- `http://localhost/MAT_Refinado_0_1_TESTE/index.php?page=fila_rapida`
- `http://localhost/MAT_Refinado_0_1_TESTE/index.php?page=rodadas`
- `http://localhost/MAT_Refinado_0_1_TESTE/index.php?page=temporis`

Atenção: se a configuração de teste apontar para seu banco real, **não realize gravações**, porque uma pasta de teste não isola o banco. Para testar alterações, clone também os bancos e ajuste a configuração/servidor SQL com as credenciais corretas.

### 3. Banco científico

- **Já tem dados na curadoria?** Não execute `database/curadoria_cientifica_schema_novo.sql`. Exporte a estrutura, compare com as tabelas `cur_rodadas`, `cur_candidatos`, `cur_fontes`, `cur_criterios`, `cur_log` e aplique somente migrações revisadas após backup.
- **Instalação totalmente nova e vazia?** Abra o phpMyAdmin e importe `database/schema.sql` para o observatório e `database/curadoria_cientifica_schema_novo.sql` para a curadoria. A curadoria nasce **sem nenhum marco**; você precisará importar seus candidatos verificados por um processo à parte. Não execute seed de outro modelo de tabelas esperando popular automaticamente `cur_candidatos`.

### 4. Testes PHP

No PowerShell, entrando na pasta da aplicação:

```powershell
cd 'C:\xampp\htdocs\MAT_Refinado_0_1_TESTE'
Get-ChildItem .\tests\test_*.php | ForEach-Object {
  & 'C:\xampp\php\php.exe' $_.FullName
  if ($LASTEXITCODE -ne 0) { throw "Falha no teste: $($_.Name)" }
}
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
  & 'C:\xampp\php\php.exe' -l $_.FullName
  if ($LASTEXITCODE -ne 0) { throw "Erro de sintaxe: $($_.Name)" }
}
```

### 5. Passo a passo da nova revisão

1. Entre como administrador e abra a **Fila rápida**. Escolha `0–10 notas` para identificar quem precisa de pesquisa ou `Todos os candidatos` para visualizar o conjunto.
2. Use o problema dominante (`Data histórica`, `Fontes`, `Rubricas`, `Pendências`, `Duplicatas`) para agrupar tarefas semelhantes.
3. Na ficha de cada candidato, cadastre as fontes verificadas, documente a data (ponto ou intervalo) e preencha notas justificadas. **Sem evidência não é nota zero.**
4. Marque somente as pendências investigadas, escolha uma fonte cadastrada para cada uma e escreva um parecer específico; salve com uma justificativa geral.
5. A aprovação exige decisão humana; somente depois proceda à homologação. Confirme que a Têmporis homologada não inclui dossiês não homologados.
6. Baixe `export_curadoria_pendencias.php` após login de administrador para guardar um retrato da fila de problemas.

### 6. Como comparar dois avaliadores

Envie a dois pesquisadores **separadamente** o modelo `sample/MODELO_PONTUACAO_INDEPENDENTE.csv`, preenchendo todas as notas `0`, `1`, `2`, `3` ou deixando em branco quando não houver evidência. Não divulgue as respostas do outro avaliador antes do registro da avaliação.

```powershell
& 'C:\xampp\php\php.exe' .\cli\concordancia_independente.php .\avaliador_A.csv .\avaliador_B.csv
```

A saída é descritiva e não substitui revisão de fontes históricas e da concordância por especialista.

### 7. Antes de substituir o projeto em uso

- Guarde backups dos arquivos e bancos e registre data/revisão.
- Confirme comportamento das rotas sob login, testes de gravação e rollback em **banco clonado**.
- Só então atualize a pasta original, preservando seu `config.local.php`, imagens e dados adicionais.
- Não execute `git push` diretamente para `main` sem conferir diferenças e revisar credenciais; prefira branch `fix/curadoria-0-1-2`.
