param(
    [string] $RootPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'
$failures = New-Object System.Collections.Generic.List[string]

Push-Location $RootPath
try {
    $paths = @('src', 'tests', 'config', 'tools')
    foreach ($path in $paths) {
        if (-not (Test-Path $path)) { continue }
        Get-ChildItem $path -Recurse -File -Filter '*.php' | ForEach-Object {
            php -l $_.FullName *> $null
            if ($LASTEXITCODE -ne 0) {
                $failures.Add($_.FullName)
            }
        }
    }

    php -l 'bin/console' *> $null
    if ($LASTEXITCODE -ne 0) {
        $failures.Add((Join-Path (Get-Location).Path 'bin/console'))
    }

    if ($failures.Count -gt 0) {
        Write-Host 'PHP syntax lint failed:'
        foreach ($failure in $failures) { Write-Host (' - ' + $failure) }
        exit 1
    }

    Write-Host 'PHP syntax lint passed.'
} finally {
    Pop-Location
}
