# Restauracao cirurgica do menu original MAT: apenas a linha "Triagem assistida · 750".
# Executar no PowerShell, a partir da raiz do MAT, depois de backup da pasta.
param([string]$PastaMAT = (Get-Location).Path)
$ErrorActionPreference = 'Stop'
$caminho = Join-Path $PastaMAT 'views\header.php'
if (!(Test-Path $caminho) -or !(Test-Path (Join-Path $PastaMAT 'fila_pontuacao.php'))) {
    throw "Pasta MAT incorreta. Execute no diretorio MAT_Refinado_0_1 ou indique -PastaMAT."
}
$conteudo = [System.IO.File]::ReadAllText($caminho, [System.Text.Encoding]::UTF8)
$expressao = '(?m)^[^\r\n]*<a href="fila_triagem_assistida\.php"[^\r\n]*Triagem assistida[^\r\n]*(?:\r?\n|$)'
$ocorrencias = [regex]::Matches($conteudo, $expressao).Count
if ($ocorrencias -eq 0) {
    Write-Host 'O menu extra ja nao existe. Nenhuma alteracao feita.'
    exit 0
}
if ($ocorrencias -ne 1) { throw "Encontradas $ocorrencias ocorrencias; por seguranca, nao alterei o arquivo." }
if ($conteudo -notmatch 'Fila de revisão rápida') { throw 'Nao identifiquei a fila original. Interrompido por seguranca.' }
$carimbo = Get-Date -Format 'yyyyMMdd_HHmmss'
$backup = Join-Path $env:TEMP "MAT_backup_header_$carimbo.php"
Copy-Item -LiteralPath $caminho -Destination $backup -ErrorAction Stop
$novo = [regex]::Replace($conteudo, $expressao, '', 1)
[System.IO.File]::WriteAllText($caminho, $novo, (New-Object System.Text.UTF8Encoding($false)))
Write-Host 'Menu original restaurado. A Fila de revisao rapida permanece igual.'
Write-Host "Backup do antigo header: $backup"
Write-Host 'Atualize a pagina com Ctrl + F5.'
Write-Host 'Nao houve alteracao no banco de dados.'
