param(
    [string] $RootPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ErrorActionPreference = 'Stop'

Push-Location $RootPath
try {
    Write-Host 'Administering local standalone checks'
    Write-Host ('Root: ' + (Get-Location).Path)

    php tools/inspection/AdministeringStandaloneReadinessReport.php
    if ($LASTEXITCODE -ne 0) {
        throw 'Standalone readiness report failed.'
    }

    if (Test-Path vendor/bin/phpstan) {
        vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G
        if ($LASTEXITCODE -ne 0) { throw 'PHPStan failed.' }
    } else {
        Write-Host 'Skip PHPStan: vendor/bin/phpstan is not installed.'
    }

    if (Test-Path vendor/bin/phpunit) {
        vendor/bin/phpunit -c phpunit.xml.dist
        if ($LASTEXITCODE -ne 0) { throw 'PHPUnit failed.' }
    } else {
        Write-Host 'Skip PHPUnit: vendor/bin/phpunit is not installed.'
    }

    if (Test-Path bin/console) {
        php bin/console lint:yaml config
        if ($LASTEXITCODE -ne 0) { throw 'Symfony YAML lint failed.' }
    }
} finally {
    Pop-Location
}
