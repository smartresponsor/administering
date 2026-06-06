param(
    [string]$Config = 'phpstan.neon',
    [string]$ReportPrefix = 'phpstan',
    [switch]$Raw,
    [switch]$Json,
    [switch]$FailOnPhpStanError
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function Write-Utf8NoBomFile {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Value
    )

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Value, $encoding)
}

function Quote-CmdArgument {
    param([Parameter(Mandatory = $true)][string]$Value)

    return '"' + ($Value -replace '"', '\"') + '"'
}

$repo = (Get-Location).Path
$configPath = Join-Path $repo $Config
$reportRoot = Join-Path $repo 'var\reports\phpstan'
New-Item -ItemType Directory -Path $reportRoot -Force | Out-Null

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$extension = 'txt'
$errorFormat = $null

if ($Json) {
    $extension = 'json'
    $errorFormat = 'json'
} elseif ($Raw) {
    $extension = 'txt'
    $errorFormat = 'raw'
}

$safePrefix = $ReportPrefix -replace '[^A-Za-z0-9_.-]', '-'
$reportFile = Join-Path $reportRoot ($safePrefix + '-' + $timestamp + '.' + $extension)
$stderrFile = Join-Path $reportRoot ($safePrefix + '-' + $timestamp + '.stderr.txt')
$exitFile = Join-Path $reportRoot ($safePrefix + '-' + $timestamp + '.exit.txt')

if (-not (Test-Path -LiteralPath $configPath)) {
    Write-Utf8NoBomFile -Path $reportFile -Value ''
    Write-Utf8NoBomFile -Path $stderrFile -Value ('PHPStan config was not found: ' + $configPath)
    Write-Utf8NoBomFile -Path $exitFile -Value '2'
    Write-Output ('phpstan_report=' + $reportFile)
    Write-Output ('phpstan_stderr=' + $stderrFile)
    Write-Output 'phpstan_exit=2'
    if ($FailOnPhpStanError) { exit 2 }
    exit 0
}

$phpstanBat = Join-Path $repo 'vendor\bin\phpstan.bat'
$phpstanBin = Join-Path $repo 'vendor\bin\phpstan'

if (Test-Path -LiteralPath $phpstanBat) {
    $commandParts = @((Quote-CmdArgument $phpstanBat))
} elseif (Test-Path -LiteralPath $phpstanBin) {
    $commandParts = @('php', (Quote-CmdArgument $phpstanBin))
} else {
    Write-Utf8NoBomFile -Path $reportFile -Value ''
    Write-Utf8NoBomFile -Path $stderrFile -Value 'PHPStan executable was not found in vendor/bin. Run composer install first.'
    Write-Utf8NoBomFile -Path $exitFile -Value '127'
    Write-Output ('phpstan_report=' + $reportFile)
    Write-Output ('phpstan_stderr=' + $stderrFile)
    Write-Output 'phpstan_exit=127'
    if ($FailOnPhpStanError) { exit 127 }
    exit 0
}

$commandParts += @('analyse', '-c', (Quote-CmdArgument $configPath), '--memory-limit=1G')

if ($errorFormat) {
    $commandParts += ('--error-format=' + $errorFormat)
}

$command = $commandParts -join ' '

$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = 'cmd.exe'
$psi.Arguments = '/d /s /c "' + $command + '"'
$psi.WorkingDirectory = $repo
$psi.UseShellExecute = $false
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError = $true
$psi.CreateNoWindow = $true

$process = New-Object System.Diagnostics.Process
$process.StartInfo = $psi
[void]$process.Start()

$stdout = $process.StandardOutput.ReadToEnd()
$stderr = $process.StandardError.ReadToEnd()
$process.WaitForExit()
$exitCode = $process.ExitCode

Write-Utf8NoBomFile -Path $reportFile -Value $stdout
Write-Utf8NoBomFile -Path $stderrFile -Value $stderr
Write-Utf8NoBomFile -Path $exitFile -Value ([string]$exitCode)

Write-Output ('phpstan_report=' + $reportFile)
Write-Output ('phpstan_stderr=' + $stderrFile)
Write-Output ('phpstan_exit=' + $exitCode)

if ($FailOnPhpStanError) {
    exit $exitCode
}

exit 0
