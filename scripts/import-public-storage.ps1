param(
    [string]$InputPath = "database/exports/public-storage.zip",
    [string]$DestinationPath = "storage/app/public"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $InputPath)) {
    throw "Archive introuvable : $InputPath"
}

New-Item -ItemType Directory -Force -Path $DestinationPath | Out-Null
Expand-Archive -LiteralPath $InputPath -DestinationPath $DestinationPath -Force

if (-not (Test-Path -LiteralPath (Join-Path $DestinationPath ".gitignore"))) {
    Set-Content -LiteralPath (Join-Path $DestinationPath ".gitignore") -Value "*`n!.gitignore" -NoNewline
}

Write-Host "Import fichiers publics termine vers : $DestinationPath"
Write-Host "Pense a executer php artisan storage:link si public/storage n'existe pas."
