param(
    [string]$EnvPath = ".env",
    [string]$InputPath = "database/exports/erp_database.sql"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $EnvPath)) {
    throw "Fichier $EnvPath introuvable."
}

if (-not (Test-Path -LiteralPath $InputPath)) {
    throw "Fichier SQL introuvable : $InputPath"
}

function Get-EnvValue([string]$Key) {
    $line = Get-Content -LiteralPath $EnvPath | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if (-not $line) { return $null }
    return ($line -replace "^$Key=", '').Trim('"')
}

$connection = Get-EnvValue "DB_CONNECTION"
if ($connection -ne "mysql") {
    throw "Import MySQL attendu, mais DB_CONNECTION=$connection."
}

$hostName = Get-EnvValue "DB_HOST"
$port = Get-EnvValue "DB_PORT"
$username = Get-EnvValue "DB_USERNAME"
$password = Get-EnvValue "DB_PASSWORD"

if (-not $username) { throw "DB_USERNAME est vide." }

$args = @(
    "--host=$hostName",
    "--port=$port",
    "--user=$username"
)

if ($password) {
    $args = @("--password=$password") + $args
}

Get-Content -LiteralPath $InputPath | & mysql @args

if ($LASTEXITCODE -ne 0) {
    throw "mysql import a echoue avec le code $LASTEXITCODE. Verifie que MySQL/XAMPP est lance et que les identifiants .env sont corrects."
}

Write-Host "Import termine depuis : $InputPath"
