param(
    [string]$Repo = (Get-Location).Path
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

php (Join-Path $PSScriptRoot 'administering-no-excluded-admin-controller-surface-guard.php') $Repo
exit $LASTEXITCODE
