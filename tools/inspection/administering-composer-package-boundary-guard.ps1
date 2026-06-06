param(
    [string] $RootPath = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'
$scriptPath = Join-Path $PSScriptRoot 'administering-composer-package-boundary-guard.php'
php $scriptPath $RootPath
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
