param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$old = 'App\Administering\Form\Administration\'
$new = 'App\Administering\Form\Admin\'
$changed = 0

$files = Get-ChildItem -LiteralPath $ProjectRoot -Recurse -File -Filter '*.php' | Where-Object {
    $_.FullName -notmatch '\\(vendor|var|node_modules|\.git|\.gating)\\'
}

foreach ($file in $files) {
    $content = Get-Content -LiteralPath $file.FullName -Raw
    if (-not $content.Contains($old)) {
        continue
    }

    $changed++
    Write-Host $file.FullName

    if ($DryRun) {
        continue
    }

    $updated = $content.Replace($old, $new)
    [System.IO.File]::WriteAllText($file.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
}

Write-Host ("Form topology files requiring update: {0}" -f $changed)

