param(
    [string]$EnvPath = ".env",
    [string]$OutputPath = "database/exports/erp_database.sql"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $EnvPath)) {
    throw "Fichier $EnvPath introuvable."
}

function Get-EnvValue([string]$Key) {
    $line = Get-Content -LiteralPath $EnvPath | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if (-not $line) { return $null }
    return ($line -replace "^$Key=", '').Trim('"')
}

$connection = Get-EnvValue "DB_CONNECTION"
if ($connection -ne "mysql") {
    throw "Export MySQL attendu, mais DB_CONNECTION=$connection."
}

$hostName = Get-EnvValue "DB_HOST"
$port = Get-EnvValue "DB_PORT"
$database = Get-EnvValue "DB_DATABASE"
$username = Get-EnvValue "DB_USERNAME"
$password = Get-EnvValue "DB_PASSWORD"

if (-not $database) { throw "DB_DATABASE est vide." }
if (-not $username) { throw "DB_USERNAME est vide." }

$outputFullPath = Join-Path (Get-Location) $OutputPath
$outputDir = Split-Path -Parent $outputFullPath
New-Item -ItemType Directory -Force -Path $outputDir | Out-Null

$args = @(
    "--host=$hostName",
    "--port=$port",
    "--user=$username",
    "--databases", $database,
    "--single-transaction",
    "--routines",
    "--triggers",
    "--events",
    "--add-drop-table",
    "--result-file=$outputFullPath"
)

if ($password) {
    $args = @("--password=$password") + $args
}

& mysqldump @args

if ($LASTEXITCODE -ne 0) {
    throw "mysqldump a echoue avec le code $LASTEXITCODE. Verifie que MySQL/XAMPP est lance et que les identifiants .env sont corrects."
}

Write-Host "Export termine : $OutputPath"
