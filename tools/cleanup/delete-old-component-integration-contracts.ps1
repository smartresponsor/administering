# From Administering repo root
$Repo = Get-Location

$DeletePaths = @(
    "src\Contract\ComponentIntegrationContractInterface.php",
    "src\Contract\ComponentIntegrationContractRegistry.php",
    "src\Contract\Accessing\AccessingComponentIntegrationContractProvider.php",
    "src\Contract\Accessing\AccessingComponentIntegrationContractStub.php",
    "src\Contract\Rolling\RollingComponentIntegrationContractProvider.php",
    "src\Provider\Connected\ComponentIntegrationContractRegistryProvider.php",
    "src\Provider\Connected\AccessingComponentIntegrationContractProvider.php",
    "src\Provider\Connected\RollingComponentIntegrationContractProvider.php"
)

Write-Host "== Delete old ComponentIntegrationContract names =="
Write-Host "Repo: $Repo"
Write-Host ""

foreach ($RelativePath in $DeletePaths) {
    $Path = Join-Path $Repo $RelativePath

    if (Test-Path -LiteralPath $Path) {
        Remove-Item -LiteralPath $Path -Force
        Write-Host "DELETED $RelativePath"
    } else {
        Write-Host "SKIP    $RelativePath"
    }
}

Write-Host ""
Write-Host "== Verify no old contract references =="
$Matches = Select-String -Path .\src\*.php,.\src\**\*.php,.\config\*.yaml,.\config\**\*.yaml `
    -Pattern 'ComponentIntegrationContractInterface|ComponentIntegrationContractRegistry|AccessingComponentIntegrationContractProvider|RollingComponentIntegrationContractProvider|AccessingComponentIntegrationContractStub' `
    -ErrorAction SilentlyContinue

if ($Matches) {
    $Matches | ForEach-Object { Write-Host "FOUND $($_.Path):$($_.LineNumber): $($_.Line.Trim())" }
    throw "Old component integration contract references remain."
}

Write-Host "OK: no old contract references."
