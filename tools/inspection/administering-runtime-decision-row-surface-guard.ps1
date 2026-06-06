param(
    [string] $RepositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
php (Join-Path $PSScriptRoot 'administering-runtime-decision-row-surface-guard.php') $RepositoryRoot
