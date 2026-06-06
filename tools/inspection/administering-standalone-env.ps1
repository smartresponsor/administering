param(
    [string] $Repo = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'

$repoPath = (Resolve-Path -LiteralPath $Repo).Path
$envPath = Join-Path $repoPath '.env'

if (-not (Test-Path -LiteralPath $envPath)) {
    Write-Output 'administering_standalone_env=missing'
    Write-Output "expected=$envPath"
    exit 1
}

$content = [System.IO.File]::ReadAllText($envPath)
$required = @('APP_ENV=', 'APP_RUNTIME_SCOPE=')
$missing = @()
foreach ($item in $required) {
    if ($content -notmatch [regex]::Escape($item)) {
        $missing += $item.TrimEnd('=')
    }
}

if ($missing.Count -gt 0) {
    Write-Output 'administering_standalone_env=invalid'
    Write-Output ('missing=' + ($missing -join ','))
    exit 1
}

Write-Output 'administering_standalone_env=ok'
Write-Output "env=$envPath"
exit 0
