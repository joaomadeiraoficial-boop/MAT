# Instala somente o snapshot EXPLORATORIO, sem alterar os bancos originais.
param(
  [string]$XamppDir = '',
  [string]$DbUser = 'root',
  [string]$DbPassword = ''
)
$ErrorActionPreference = 'Stop'
$projectDir = $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($XamppDir)) {
  $XamppDir = (Resolve-Path (Join-Path $projectDir '..\..')).Path
}
$mysql = Join-Path $XamppDir 'mysql\bin\mysql.exe'
if (-not (Test-Path $mysql)) {
  throw "mysql.exe nao encontrado. Informe -XamppDir 'C:\xampp' (ou o seu caminho)."
}
$dumpFile = Join-Path $projectDir 'instalacao\mat_final_curadoria_snapshot.sql'
if (-not (Test-Path $dumpFile)) {throw "Snapshot SQL nao encontrado: $dumpFile"}
$db='mat_final_curadoria'
$env:MYSQL_PWD = $DbPassword
$argsMysql=@("-u$DbUser",'--default-character-set=utf8mb4')
function Query([string]$sql) {
  $res = & $mysql @argsMysql --batch --skip-column-names --execute=$sql 2>&1
  if ($LASTEXITCODE -ne 0) {throw "Falha consultando MySQL: $res"}
  return (($res | Out-String).Trim())
}
if ([int](Query "SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$db'") -gt 0) {
  throw "PROTECAO: $db ja existe. NENHUMA alteracao realizada. Faça backup e valide manualmente antes de importar."
}
Write-Host 'Criando SOMENTE mat_final_curadoria (os bancos do Refinado nao sao alterados)...' -ForegroundColor Cyan
& $mysql @argsMysql --execute="CREATE DATABASE $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if ($LASTEXITCODE -ne 0) {throw 'Falha ao criar o novo banco.'}
$psi=New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName=$mysql
$psi.Arguments=('-u' + $DbUser + ' --default-character-set=utf8mb4 --database=' + $db)
$psi.UseShellExecute=$false
$psi.RedirectStandardInput=$true
$psi.RedirectStandardError=$true
$psi.CreateNoWindow=$true
if ($DbPassword -ne '') {$psi.EnvironmentVariables['MYSQL_PWD']=$DbPassword}
$process=[System.Diagnostics.Process]::Start($psi)
try {
  $file=[System.IO.File]::OpenRead($dumpFile)
  try {$file.CopyTo($process.StandardInput.BaseStream)} finally {$file.Close()}
  $process.StandardInput.Close()
  $errors=$process.StandardError.ReadToEnd()
  $process.WaitForExit()
  if ($process.ExitCode -ne 0) {throw "Falha ao importar snapshot: $errors"}
} finally {$process.Dispose()}
$rows = Query 'SELECT COUNT(*) FROM mat_final_curadoria.cur_candidatos'
Write-Host "Snapshot importado: $rows candidatos. Os bancos originais continuam inalterados." -ForegroundColor Green
$php = Join-Path $XamppDir 'php\php.exe'
if (Test-Path $php) {
  & $php (Join-Path $projectDir 'tests\verificar_bancos_mat_final.php')
  if ($LASTEXITCODE -ne 0) {throw 'A verificacao integrada falhou; consulte a saida acima.'}
} else {Write-Warning 'PHP CLI nao encontrado; teste integrado nao executado.'}
Write-Host 'Abrir: http://localhost/MAT_Refinado_0_1/index.php?page=temporis_final'
