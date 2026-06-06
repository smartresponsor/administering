param(
    [string] $RootPath = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'
$scriptPath = Join-Path $PSScriptRoot 'administering-zero-foreign-coupling-guard.php'
php $scriptPath $RootPath
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
