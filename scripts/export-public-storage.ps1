param(
    [string]$SourcePath = "storage/app/public",
    [string]$OutputPath = "database/exports/public-storage.zip"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $SourcePath)) {
    throw "Dossier source introuvable : $SourcePath"
}

$outputFullPath = Join-Path (Get-Location) $OutputPath
$outputDir = Split-Path -Parent $outputFullPath
$tempDir = Join-Path ([System.IO.Path]::GetTempPath()) ("exad-public-storage-" + [System.Guid]::NewGuid().ToString("N"))

New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

try {
    Get-ChildItem -LiteralPath $SourcePath -Force | Where-Object { $_.Name -ne ".gitignore" } | ForEach-Object {
        $destination = Join-Path $tempDir $_.Name

        if ($_.PSIsContainer) {
            Copy-Item -LiteralPath $_.FullName -Destination $destination -Recurse -Force
        } else {
            Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
        }
    }

    if (Test-Path -LiteralPath $outputFullPath) {
        Remove-Item -LiteralPath $outputFullPath -Force
    }

    $archiveItems = Get-ChildItem -LiteralPath $tempDir -Force

    if ($archiveItems.Count -eq 0) {
        Write-Host "Aucun fichier public a exporter."
        return
    }

    Compress-Archive -Path (Join-Path $tempDir "*") -DestinationPath $outputFullPath -Force
} finally {
    if (Test-Path -LiteralPath $tempDir) {
        Remove-Item -LiteralPath $tempDir -Recurse -Force
    }
}

Write-Host "Export fichiers publics termine : $OutputPath"
