param(
    [string] $RootPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
php (Join-Path $PSScriptRoot 'administering-runtime-output-schema-guard.php') $RootPath
exit $LASTEXITCODE
