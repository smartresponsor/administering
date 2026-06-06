param(
    [string]$Repo = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path,
    [string]$Config = 'phpstan.neon',
    [string]$OutputDir = 'var/reports/phpstan',
    [string]$MemoryLimit = '1G',
    [switch]$Json,
    [switch]$Raw
)

$ErrorActionPreference = 'Stop'

$repoPath = (Resolve-Path $Repo).Path
$configPath = Join-Path $repoPath $Config
$vendorPhpstan = Join-Path $repoPath 'vendor/bin/phpstan'
$vendorPhpstanBat = Join-Path $repoPath 'vendor/bin/phpstan.bat'
$outputPath = Join-Path $repoPath $OutputDir
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'

if (-not (Test-Path $configPath)) {
    Write-Output "PHPStan config not found: $configPath"
    exit 2
}

if (-not (Test-Path $outputPath)) {
    New-Item -ItemType Directory -Path $outputPath -Force | Out-Null
}

if ($Json) {
    $format = 'json'
    $extension = 'json'
} elseif ($Raw) {
    $format = 'raw'
    $extension = 'txt'
} else {
    $format = 'table'
    $extension = 'txt'
}

$reportFile = Join-Path $outputPath "phpstan-$timestamp.$extension"
$stderrFile = Join-Path $outputPath "phpstan-$timestamp.stderr.txt"
$exitFile = Join-Path $outputPath "phpstan-$timestamp.exit.txt"

if (Test-Path $vendorPhpstanBat) {
    $phpstanCommand = $vendorPhpstanBat
    $phpstanArgs = @('analyse', '-c', $configPath, '--memory-limit', $MemoryLimit, '--no-progress', '--error-format', $format)
} elseif (Test-Path $vendorPhpstan) {
    $phpstanCommand = $vendorPhpstan
    $phpstanArgs = @('analyse', '-c', $configPath, '--memory-limit', $MemoryLimit, '--no-progress', '--error-format', $format)
} else {
    Write-Output "PHPStan executable not found under vendor/bin. Run composer install first."
    exit 3
}

Push-Location $repoPath
try {
    & $phpstanCommand @phpstanArgs 1> $reportFile 2> $stderrFile
    $exitCode = $LASTEXITCODE
} finally {
    Pop-Location
}

Set-Content -Path $exitFile -Value ([string]$exitCode) -Encoding UTF8

Write-Output "phpstan_report=$reportFile"
Write-Output "phpstan_stderr=$stderrFile"
Write-Output "phpstan_exit=$exitCode"

exit $exitCode
