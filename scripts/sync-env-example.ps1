param(
    [string]$EnvPath = ".env",
    [string]$ExamplePath = ".env.example"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $EnvPath)) {
    throw "Fichier $EnvPath introuvable."
}

$sensitivePatterns = @(
    '^APP_KEY$',
    'PASSWORD',
    'SECRET',
    'TOKEN',
    'PRIVATE',
    'ACCESS_KEY',
    'API_KEY'
)

$lines = Get-Content -LiteralPath $EnvPath
$output = foreach ($line in $lines) {
    if ($line -match '^\s*$' -or $line -match '^\s*#') {
        $line
        continue
    }

    if ($line -notmatch '^([^=]+)=(.*)$') {
        $line
        continue
    }

    $key = $matches[1].Trim()
    $value = $matches[2]
    $isSensitive = $false

    foreach ($pattern in $sensitivePatterns) {
        if ($key -match $pattern) {
            $isSensitive = $true
            break
        }
    }

    if ($isSensitive) {
        "$key="
    } else {
        "$key=$value"
    }
}

$output | Set-Content -LiteralPath $ExamplePath -Encoding UTF8
Write-Host "Synchronisation terminee : $ExamplePath a ete mis a jour depuis $EnvPath sans secrets."
