param(
    [string]$RootPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
& php (Join-Path $PSScriptRoot 'administering-composer-inventory-evidence-guard.php') $RootPath
exit $LASTEXITCODE
