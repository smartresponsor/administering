param(
    [string] $Repo = (Get-Location).Path,
    [string] $HostDir = ""
)

$ErrorActionPreference = 'Stop'

function Write-Step([string] $Message) {
    Write-Host "== $Message =="
}

function Assert-NoPattern([string] $Pattern, [string[]] $Paths, [string] $Description) {
    $existing = @()
    foreach ($path in $Paths) {
        $full = Join-Path $Repo $path
        if (Test-Path -LiteralPath $full) {
            $existing += $full
        }
    }

    if ($existing.Count -eq 0) {
        return
    }

    $matches = Select-String -Path $existing -Pattern $Pattern -Recurse -ErrorAction SilentlyContinue
    if ($matches) {
        Write-Host "FAILED: $Description"
        $matches | Select-Object -First 30 | ForEach-Object {
            Write-Host ("{0}:{1}: {2}" -f $_.Path, $_.LineNumber, $_.Line.Trim())
        }
        throw "Pattern check failed: $Description"
    }
}

function Assert-PhpSyntax([string[]] $Files) {
    foreach ($relative in $Files) {
        $file = Join-Path $Repo $relative
        if (Test-Path -LiteralPath $file) {
            php -l $file | Out-Host
            if ($LASTEXITCODE -ne 0) {
                throw "PHP syntax failed: $relative"
            }
        }
    }
}

Write-Step "Administering runtime-scope smoke"
Write-Host "Repo: $Repo"
if ($HostDir -ne '') {
    Write-Host "HostDir: $HostDir"
}

Write-Step "Forbidden drift markers"
Assert-NoPattern 'component_dependency_catalog' @('src','config') 'removed component dependency catalog must not be referenced'
Assert-NoPattern 'AdministrationOptionalIntegration' @('src','config') 'removed optional-integration classes must not be referenced'
Assert-NoPattern 'SMART_' @('src','config') 'old SMART_ runtime-scope keys must not be used in active source/config/tools'
Assert-NoPattern 'ADMINISTRATION_RUNTIME_COMPONENT_PROFILE' @('src','config') 'old profile env selector must not be used in active source/config/tools'
Assert-NoPattern 'AdministeringCredentialConfig' @('src','config') 'old AdministeringCredentialConfig class names must not be used'
Assert-NoPattern 'AdministeringIntegrationConfig' @('src','config') 'old AdministeringIntegrationConfig class names must not be used'

Write-Step "Runtime-scope PHP syntax"
Assert-PhpSyntax @(
    'src/Command/AdministrationRuntimeScopeExportCommand.php',
    'src/Command/AdministrationRuntimeScopeValidateCommand.php',
    'src/Command/AdministrationRuntimeScopeReportCommand.php',
    'src/Command/AdministrationRuntimeScopeReferenceAuditCommand.php',
    'src/Command/AdministrationRuntimeScopeKernelRecipeCommand.php',
    'src/Service/RuntimeScope/AdministrationRuntimeScopeExportService.php',
    'src/Service/RuntimeScope/AdministrationRuntimeScopeValidationService.php',
    'src/Service/RuntimeScope/AdministrationRuntimeScopeKernelRecipeService.php',
    'src/Reader/RuntimeScope/AdministrationRuntimeScopeStateReader.php',
    'src/Service/RuntimeScope/AdministrationRuntimeScopeDecisionService.php',
    'src/Value/RuntimeScope/AdministrationRuntimeScopeDecision.php',
    'src/Scanner/RuntimeScope/AdministrationRuntimeScopeReferenceScanner.php',
    'src/Scanner/RuntimeScope/AdministrationRuntimeScopeConfigLeakScanner.php',
    'src/Factory/RuntimeScope/AdministrationRuntimeScopePhpLockSourceFactory.php',
    'src/Value/RuntimeScope/AdministrationRuntimeScopeState.php'
)

if ($HostDir -ne '') {
    Write-Step "Runtime-scope commands against host"
    php bin/console administering:runtime-scope:report --env=dev --host-dir=$HostDir --json | Out-Host
    php bin/console administering:runtime-scope:validate --env=dev --host-dir=$HostDir --json | Out-Host
    php bin/console administering:runtime-scope:reference-audit --env=dev --host-dir=$HostDir --json | Out-Host
}

Write-Step "Smoke passed"
