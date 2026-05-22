# capture-administering-rc-proof.ps1
# Non-destructive Administering 3RC proof capture helper.
# Run from any directory, or pass -ProjectRoot explicitly.

param(
    [string] $ProjectRoot = (Get-Location).Path,
    [string] $OperationType = "administration.configuration.scan",
    [string] $TargetPrefix = "administering:rc-proof",
    [string] $OutputFile = "delivery\rc\runtime-proof-results\administering-rc-proof.json",
    [string] $IndexFile = "delivery\rc\runtime-proof-results\administering-rc-proof-index.json",
    [string] $ValidationFile = "delivery\rc\runtime-proof-results\administering-rc-proof-validation.json",
    [string] $OwnerReviewFile = "delivery\rc\runtime-proof-results\administering-rc-owner-review.json",
    [string] $FinalSealFile = "delivery\rc\runtime-proof-results\administering-rc-final-seal.json",
    [string] $FinalSealValidationFile = "delivery\rc\runtime-proof-results\administering-rc-final-seal-validation.json",
    [string] $StatusFile = "delivery\rc\runtime-proof-results\administering-rc-status.json",
    [string] $StatusSummaryFile = "delivery\rc\runtime-proof-results\administering-rc-status-summary.txt",
    [string] $ReceiptFile = "delivery\rc\runtime-proof-results\administering-rc-receipt.json",
    [string] $ReceiptTextFile = "delivery\rc\runtime-proof-results\administering-rc-receipt.txt",
    [string] $ReceiptValidationFile = "delivery\rc\runtime-proof-results\administering-rc-receipt-validation.json",
    [string] $FinalStatusFile = "delivery\rc\runtime-proof-results\administering-rc-final-status.json",
    [string] $FinalStatusSummaryFile = "delivery\rc\runtime-proof-results\administering-rc-final-status-summary.txt",
    [string] $FinalStatusValidationFile = "delivery\rc\runtime-proof-results\administering-rc-final-status-validation.json",
    [string] $HandoffIndexFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-index.json",
    [string] $HandoffIndexTextFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-index.txt",
    [string] $HandoffIndexValidationFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-index-validation.json",
    [string] $TerminalStatusFile = "delivery\rc\runtime-proof-results\administering-rc-terminal-status.json",
    [string] $TerminalStatusSummaryFile = "delivery\rc\runtime-proof-results\administering-rc-terminal-status-summary.txt",
    [string] $TerminalStatusValidationFile = "delivery\rc\runtime-proof-results\administering-rc-terminal-status-validation.json",
    [string] $HandoffBundleFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-bundle.json",
    [string] $HandoffBundleTextFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-bundle.txt",
    [string] $HandoffBundleValidationFile = "delivery\rc\runtime-proof-results\administering-rc-handoff-bundle-validation.json",
    [string] $BundleStatusFile = "delivery\rc\runtime-proof-results\administering-rc-bundle-status.json",
    [string] $BundleStatusSummaryFile = "delivery\rc\runtime-proof-results\administering-rc-bundle-status-summary.txt",
    [string] $AcceptanceFile = "delivery\rc\runtime-proof-results\administering-rc-acceptance.json",
    [string] $AcceptanceTextFile = "delivery\rc\runtime-proof-results\administering-rc-acceptance.txt",
    [string] $ManifestFile = "delivery\rc\manifest.yaml"
)

$ErrorActionPreference = "Stop"

function Resolve-ProjectPath {
    param(
        [Parameter(Mandatory = $true)] [string] $PathValue,
        [Parameter(Mandatory = $true)] [string] $Root
    )

    if ([System.IO.Path]::IsPathRooted($PathValue)) {
        return $PathValue
    }

    return (Join-Path $Root $PathValue)
}

if (-not (Test-Path -LiteralPath $ProjectRoot)) {
    throw "Project root not found: $ProjectRoot"
}

$consolePath = Join-Path $ProjectRoot "bin\console"
if (-not (Test-Path -LiteralPath $consolePath)) {
    throw "Symfony console not found: $consolePath"
}

$outputPath = Resolve-ProjectPath -PathValue $OutputFile -Root $ProjectRoot
$indexPath = Resolve-ProjectPath -PathValue $IndexFile -Root $ProjectRoot
$manifestPath = Resolve-ProjectPath -PathValue $ManifestFile -Root $ProjectRoot
$validationPath = Resolve-ProjectPath -PathValue $ValidationFile -Root $ProjectRoot
$ownerReviewPath = Resolve-ProjectPath -PathValue $OwnerReviewFile -Root $ProjectRoot
$finalSealPath = Resolve-ProjectPath -PathValue $FinalSealFile -Root $ProjectRoot
$finalSealValidationPath = Resolve-ProjectPath -PathValue $FinalSealValidationFile -Root $ProjectRoot
$statusPath = Resolve-ProjectPath -PathValue $StatusFile -Root $ProjectRoot
$statusSummaryPath = Resolve-ProjectPath -PathValue $StatusSummaryFile -Root $ProjectRoot
$receiptPath = Resolve-ProjectPath -PathValue $ReceiptFile -Root $ProjectRoot
$receiptTextPath = Resolve-ProjectPath -PathValue $ReceiptTextFile -Root $ProjectRoot
$receiptValidationPath = Resolve-ProjectPath -PathValue $ReceiptValidationFile -Root $ProjectRoot
$finalStatusPath = Resolve-ProjectPath -PathValue $FinalStatusFile -Root $ProjectRoot
$finalStatusSummaryPath = Resolve-ProjectPath -PathValue $FinalStatusSummaryFile -Root $ProjectRoot
$finalStatusValidationPath = Resolve-ProjectPath -PathValue $FinalStatusValidationFile -Root $ProjectRoot
$handoffIndexPath = Resolve-ProjectPath -PathValue $HandoffIndexFile -Root $ProjectRoot
$handoffIndexTextPath = Resolve-ProjectPath -PathValue $HandoffIndexTextFile -Root $ProjectRoot
$handoffIndexValidationPath = Resolve-ProjectPath -PathValue $HandoffIndexValidationFile -Root $ProjectRoot
$terminalStatusPath = Resolve-ProjectPath -PathValue $TerminalStatusFile -Root $ProjectRoot
$terminalStatusSummaryPath = Resolve-ProjectPath -PathValue $TerminalStatusSummaryFile -Root $ProjectRoot
$terminalStatusValidationPath = Resolve-ProjectPath -PathValue $TerminalStatusValidationFile -Root $ProjectRoot
$handoffBundlePath = Resolve-ProjectPath -PathValue $HandoffBundleFile -Root $ProjectRoot
$handoffBundleTextPath = Resolve-ProjectPath -PathValue $HandoffBundleTextFile -Root $ProjectRoot
$handoffBundleValidationPath = Resolve-ProjectPath -PathValue $HandoffBundleValidationFile -Root $ProjectRoot
$bundleStatusPath = Resolve-ProjectPath -PathValue $BundleStatusFile -Root $ProjectRoot
$bundleStatusSummaryPath = Resolve-ProjectPath -PathValue $BundleStatusSummaryFile -Root $ProjectRoot
$acceptancePath = Resolve-ProjectPath -PathValue $AcceptanceFile -Root $ProjectRoot
$acceptanceTextPath = Resolve-ProjectPath -PathValue $AcceptanceTextFile -Root $ProjectRoot

$acceptanceDir = Split-Path -Parent $acceptancePath
if (-not (Test-Path -LiteralPath $acceptanceDir)) {
    New-Item -ItemType Directory -Path $acceptanceDir -Force | Out-Null
}

$acceptanceTextDir = Split-Path -Parent $acceptanceTextPath
if (-not (Test-Path -LiteralPath $acceptanceTextDir)) {
    New-Item -ItemType Directory -Path $acceptanceTextDir -Force | Out-Null
}

if (-not (Test-Path -LiteralPath $manifestPath)) {
    throw "RC manifest not found: $manifestPath"
}

$outputDir = Split-Path -Parent $outputPath
if (-not (Test-Path -LiteralPath $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$indexDir = Split-Path -Parent $indexPath
if (-not (Test-Path -LiteralPath $indexDir)) {
    New-Item -ItemType Directory -Path $indexDir -Force | Out-Null
}

$validationDir = Split-Path -Parent $validationPath
if (-not (Test-Path -LiteralPath $validationDir)) {
    New-Item -ItemType Directory -Path $validationDir -Force | Out-Null
}

$ownerReviewDir = Split-Path -Parent $ownerReviewPath
if (-not (Test-Path -LiteralPath $ownerReviewDir)) {
    New-Item -ItemType Directory -Path $ownerReviewDir -Force | Out-Null
}

$finalSealDir = Split-Path -Parent $finalSealPath
if (-not (Test-Path -LiteralPath $finalSealDir)) {
    New-Item -ItemType Directory -Path $finalSealDir -Force | Out-Null
}

$finalSealValidationDir = Split-Path -Parent $finalSealValidationPath
if (-not (Test-Path -LiteralPath $finalSealValidationDir)) {
    New-Item -ItemType Directory -Path $finalSealValidationDir -Force | Out-Null
}

$statusDir = Split-Path -Parent $statusPath
if (-not (Test-Path -LiteralPath $statusDir)) {
    New-Item -ItemType Directory -Path $statusDir -Force | Out-Null
}

$statusSummaryDir = Split-Path -Parent $statusSummaryPath
if (-not (Test-Path -LiteralPath $statusSummaryDir)) {
    New-Item -ItemType Directory -Path $statusSummaryDir -Force | Out-Null
}

$receiptDir = Split-Path -Parent $receiptPath
if (-not (Test-Path -LiteralPath $receiptDir)) {
    New-Item -ItemType Directory -Path $receiptDir -Force | Out-Null
}

$receiptTextDir = Split-Path -Parent $receiptTextPath
if (-not (Test-Path -LiteralPath $receiptTextDir)) {
    New-Item -ItemType Directory -Path $receiptTextDir -Force | Out-Null
}

$receiptValidationDir = Split-Path -Parent $receiptValidationPath
if (-not (Test-Path -LiteralPath $receiptValidationDir)) {
    New-Item -ItemType Directory -Path $receiptValidationDir -Force | Out-Null
}

$finalStatusDir = Split-Path -Parent $finalStatusPath
if (-not (Test-Path -LiteralPath $finalStatusDir)) {
    New-Item -ItemType Directory -Path $finalStatusDir -Force | Out-Null
}

$finalStatusSummaryDir = Split-Path -Parent $finalStatusSummaryPath
if (-not (Test-Path -LiteralPath $finalStatusSummaryDir)) {
    New-Item -ItemType Directory -Path $finalStatusSummaryDir -Force | Out-Null
}

$finalStatusValidationDir = Split-Path -Parent $finalStatusValidationPath
if (-not (Test-Path -LiteralPath $finalStatusValidationDir)) {
    New-Item -ItemType Directory -Path $finalStatusValidationDir -Force | Out-Null
}

$handoffIndexDir = Split-Path -Parent $handoffIndexPath
if (-not (Test-Path -LiteralPath $handoffIndexDir)) {
    New-Item -ItemType Directory -Path $handoffIndexDir -Force | Out-Null
}

$handoffIndexTextDir = Split-Path -Parent $handoffIndexTextPath
if (-not (Test-Path -LiteralPath $handoffIndexTextDir)) {
    New-Item -ItemType Directory -Path $handoffIndexTextDir -Force | Out-Null
}

$handoffIndexValidationDir = Split-Path -Parent $handoffIndexValidationPath
if (-not (Test-Path -LiteralPath $handoffIndexValidationDir)) {
    New-Item -ItemType Directory -Path $handoffIndexValidationDir -Force | Out-Null
}

$handoffBundleValidationDir = Split-Path -Parent $handoffBundleValidationPath
if (-not (Test-Path -LiteralPath $handoffBundleValidationDir)) {
    New-Item -ItemType Directory -Path $handoffBundleValidationDir -Force | Out-Null
}

Push-Location $ProjectRoot
try {
    Write-Host "[administering-rc-proof] Running aggregate proof..."
    php bin/console administering:rc:proof `
        --operation-type="$OperationType" `
        --target-prefix="$TargetPrefix" `
        --output-file="$outputPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:proof failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $outputPath)) {
        throw "Expected proof output file was not written: $outputPath"
    }

    Write-Host "[administering-rc-proof] Building proof index..."
    php bin/console administering:rc:proof-index `
        --proof-file="$outputPath" `
        --manifest-file="$manifestPath" `
        --output-file="$indexPath" `
        --operation-type="$OperationType" `
        --target-prefix="$TargetPrefix" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:proof-index failed with exit code $LASTEXITCODE"
    }

    $proofHash = (Get-FileHash -LiteralPath $outputPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Validating captured artifacts..."
    php bin/console administering:rc:proof-artifact:validate `
        --proof-file="$outputPath" `
        --index-file="$indexPath" `
        --manifest-file="$manifestPath" `
        --output-file="$validationPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:proof-artifact:validate failed with exit code $LASTEXITCODE"
    }

    $validationHash = (Get-FileHash -LiteralPath $validationPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Building owner-review verdict..."
    php bin/console administering:rc:owner-review `
        --proof-file="$outputPath" `
        --index-file="$indexPath" `
        --validation-file="$validationPath" `
        --manifest-file="$manifestPath" `
        --output-file="$ownerReviewPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:owner-review failed with exit code $LASTEXITCODE"
    }

    $ownerReviewHash = (Get-FileHash -LiteralPath $ownerReviewPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Building final 3RC seal..."
    php bin/console administering:rc:final-seal `
        --proof-file="$outputPath" `
        --index-file="$indexPath" `
        --validation-file="$validationPath" `
        --owner-review-file="$ownerReviewPath" `
        --manifest-file="$manifestPath" `
        --output-file="$finalSealPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:final-seal failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $finalSealPath)) {
        throw "Expected final seal file was not written: $finalSealPath"
    }

    $finalSealHash = (Get-FileHash -LiteralPath $finalSealPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Validating final 3RC seal..."
    php bin/console administering:rc:final-seal:validate `
        --final-seal-file="$finalSealPath" `
        --proof-file="$outputPath" `
        --index-file="$indexPath" `
        --validation-file="$validationPath" `
        --owner-review-file="$ownerReviewPath" `
        --manifest-file="$manifestPath" `
        --output-file="$finalSealValidationPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:final-seal:validate failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $finalSealValidationPath)) {
        throw "Expected final seal validation file was not written: $finalSealValidationPath"
    }

    $finalSealValidationHash = (Get-FileHash -LiteralPath $finalSealValidationPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Building final 3RC status summary..."
    php bin/console administering:rc:status `
        --manifest-file="$manifestPath" `
        --proof-file="$outputPath" `
        --index-file="$indexPath" `
        --validation-file="$validationPath" `
        --owner-review-file="$ownerReviewPath" `
        --final-seal-file="$finalSealPath" `
        --final-seal-validation-file="$finalSealValidationPath" `
        --output-file="$statusPath" `
        --summary-file="$statusSummaryPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:status failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $statusPath)) {
        throw "Expected RC status file was not written: $statusPath"
    }

    if (-not (Test-Path -LiteralPath $statusSummaryPath)) {
        throw "Expected RC status summary file was not written: $statusSummaryPath"
    }

    $statusHash = (Get-FileHash -LiteralPath $statusPath -Algorithm SHA256).Hash.ToLowerInvariant()
    $statusSummaryHash = (Get-FileHash -LiteralPath $statusSummaryPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Building final 3RC owner receipt..."
    php bin/console administering:rc:receipt `
        --manifest-file="$manifestPath" `
        --status-file="$statusPath" `
        --status-summary-file="$statusSummaryPath" `
        --final-seal-validation-file="$finalSealValidationPath" `
        --output-file="$receiptPath" `
        --text-file="$receiptTextPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:receipt failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $receiptPath)) {
        throw "Expected RC receipt file was not written: $receiptPath"
    }

    if (-not (Test-Path -LiteralPath $receiptTextPath)) {
        throw "Expected RC receipt text file was not written: $receiptTextPath"
    }

    $receiptHash = (Get-FileHash -LiteralPath $receiptPath -Algorithm SHA256).Hash.ToLowerInvariant()
    $receiptTextHash = (Get-FileHash -LiteralPath $receiptTextPath -Algorithm SHA256).Hash.ToLowerInvariant()

    Write-Host "[administering-rc-proof] Validating final 3RC owner receipt..."
    php bin/console administering:rc:receipt:validate `
        --manifest-file="$manifestPath" `
        --status-file="$statusPath" `
        --status-summary-file="$statusSummaryPath" `
        --final-seal-validation-file="$finalSealValidationPath" `
        --receipt-file="$receiptPath" `
        --receipt-text-file="$receiptTextPath" `
        --output-file="$receiptValidationPath" `
        --json

    if ($LASTEXITCODE -ne 0) {
        throw "administering:rc:receipt:validate failed with exit code $LASTEXITCODE"
    }

    if (-not (Test-Path -LiteralPath $receiptValidationPath)) {
        throw "Expected RC receipt validation file was not written: $receiptValidationPath"
    }

    $receiptValidationHash = (Get-FileHash -LiteralPath $receiptValidationPath -Algorithm SHA256).Hash.ToLowerInvariant()

    
Write-Host "[administering-rc-proof] Capturing final status including receipt validation..."
& php bin/console administering:rc:status `
  --manifest-file="delivery/rc/manifest.yaml" `
  --proof-file="delivery/rc/runtime-proof-results/administering-rc-proof.json" `
  --index-file="delivery/rc/runtime-proof-results/administering-rc-proof-index.json" `
  --validation-file="delivery/rc/runtime-proof-results/administering-rc-proof-validation.json" `
  --owner-review-file="delivery/rc/runtime-proof-results/administering-rc-owner-review.json" `
  --final-seal-file="delivery/rc/runtime-proof-results/administering-rc-final-seal.json" `
  --final-seal-validation-file="delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json" `
  --receipt-file="delivery/rc/runtime-proof-results/administering-rc-receipt.json" `
  --receipt-text-file="delivery/rc/runtime-proof-results/administering-rc-receipt.txt" `
  --receipt-validation-file="delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json" `
  --include-receipt-artifacts `
  --output-file="$finalStatusPath" `
  --summary-file="$finalStatusSummaryPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:status final failed" }



Write-Host "[administering-rc-proof] Validating terminal final 3RC status..."
& php bin/console administering:rc:final-status:validate `
  --manifest-file="delivery/rc/manifest.yaml" `
  --final-status-file="$finalStatusPath" `
  --final-status-summary-file="$finalStatusSummaryPath" `
  --receipt-file="delivery/rc/runtime-proof-results/administering-rc-receipt.json" `
  --receipt-text-file="delivery/rc/runtime-proof-results/administering-rc-receipt.txt" `
  --receipt-validation-file="delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json" `
  --final-seal-validation-file="delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json" `
  --output-file="delivery/rc/runtime-proof-results/administering-rc-final-status-validation.json" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:final-status:validate failed" }



Write-Host "[administering-rc-proof] Building terminal 3RC handoff index..."
& php bin/console administering:rc:handoff-index `
  --manifest-file="$manifestPath" `
  --final-status-file="$finalStatusPath" `
  --final-status-summary-file="$finalStatusSummaryPath" `
  --final-status-validation-file="$finalStatusValidationPath" `
  --receipt-file="$receiptPath" `
  --receipt-text-file="$receiptTextPath" `
  --receipt-validation-file="$receiptValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --output-file="$handoffIndexPath" `
  --text-file="$handoffIndexTextPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:handoff-index failed" }

Write-Host "[administering-rc-proof] Validating terminal 3RC handoff index..."
& php bin/console administering:rc:handoff-index:validate `
  --manifest-file="$manifestPath" `
  --final-status-file="$finalStatusPath" `
  --final-status-summary-file="$finalStatusSummaryPath" `
  --final-status-validation-file="$finalStatusValidationPath" `
  --receipt-file="$receiptPath" `
  --receipt-text-file="$receiptTextPath" `
  --receipt-validation-file="$receiptValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --output-file="$handoffIndexValidationPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:handoff-index:validate failed" }

Write-Host "[administering-rc-proof] Capturing terminal 3RC status including handoff-index validation..."
& php bin/console administering:rc:status `
  --manifest-file="$manifestPath" `
  --proof-file="$outputPath" `
  --index-file="$indexPath" `
  --validation-file="$validationPath" `
  --owner-review-file="$ownerReviewPath" `
  --final-seal-file="$finalSealPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --receipt-file="$receiptPath" `
  --receipt-text-file="$receiptTextPath" `
  --receipt-validation-file="$receiptValidationPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --handoff-index-validation-file="$handoffIndexValidationPath" `
  --include-receipt-artifacts `
  --include-handoff-artifacts `
  --output-file="$terminalStatusPath" `
  --summary-file="$terminalStatusSummaryPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:status terminal failed" }


Write-Host "[administering-rc-proof] Validating terminal 3RC status..."
& php bin/console administering:rc:terminal-status:validate `
  --manifest-file="$manifestPath" `
  --terminal-status-file="$terminalStatusPath" `
  --terminal-status-summary-file="$terminalStatusSummaryPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --handoff-index-validation-file="$handoffIndexValidationPath" `
  --final-status-validation-file="$finalStatusValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --output-file="$terminalStatusValidationPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:terminal-status:validate failed" }



Write-Host "[administering-rc-proof] Building terminal 3RC handoff bundle..."
& php bin/console administering:rc:handoff-bundle `
  --manifest-file="$manifestPath" `
  --terminal-status-file="$terminalStatusPath" `
  --terminal-status-summary-file="$terminalStatusSummaryPath" `
  --terminal-status-validation-file="$terminalStatusValidationPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --handoff-index-validation-file="$handoffIndexValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --output-file="$handoffBundlePath" `
  --text-file="$handoffBundleTextPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:handoff-bundle failed" }

Write-Host "[administering-rc-proof] Validating terminal 3RC handoff bundle..."
& php bin/console administering:rc:handoff-bundle:validate `
  --manifest-file="$manifestPath" `
  --terminal-status-file="$terminalStatusPath" `
  --terminal-status-summary-file="$terminalStatusSummaryPath" `
  --terminal-status-validation-file="$terminalStatusValidationPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --handoff-index-validation-file="$handoffIndexValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --handoff-bundle-file="$handoffBundlePath" `
  --handoff-bundle-text-file="$handoffBundleTextPath" `
  --output-file="$handoffBundleValidationPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:handoff-bundle:validate failed" }


Write-Host "[administering-rc-proof] Capturing bundle-aware terminal 3RC status..."
& php bin/console administering:rc:status `
  --include-receipt-artifacts `
  --include-handoff-artifacts `
  --include-handoff-bundle-artifacts `
  --output-file="$bundleStatusPath" `
  --summary-file="$bundleStatusSummaryPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:status bundle-aware capture failed" }

Write-Host "[administering-rc-proof] Creating terminal 3RC acceptance marker..."
& php bin/console administering:rc:acceptance `
  --manifest-file="$manifestPath" `
  --bundle-status-file="$bundleStatusPath" `
  --bundle-status-summary-file="$bundleStatusSummaryPath" `
  --handoff-bundle-file="$handoffBundlePath" `
  --handoff-bundle-text-file="$handoffBundleTextPath" `
  --handoff-bundle-validation-file="$handoffBundleValidationPath" `
  --terminal-status-file="$terminalStatusPath" `
  --terminal-status-summary-file="$terminalStatusSummaryPath" `
  --terminal-status-validation-file="$terminalStatusValidationPath" `
  --handoff-index-file="$handoffIndexPath" `
  --handoff-index-text-file="$handoffIndexTextPath" `
  --handoff-index-validation-file="$handoffIndexValidationPath" `
  --final-seal-validation-file="$finalSealValidationPath" `
  --output-file="$acceptancePath" `
  --text-file="$acceptanceTextPath" `
  --json
if ($LASTEXITCODE -ne 0) { throw "administering:rc:acceptance failed" }

Write-Host "[administering-rc-proof] DONE"
    Write-Host "[administering-rc-proof] Output: $outputPath"
    Write-Host "[administering-rc-proof] Output SHA-256: $proofHash"
    Write-Host "[administering-rc-proof] Index: $indexPath"
    Write-Host "[administering-rc-proof] Validation: $validationPath"
    Write-Host "[administering-rc-proof] Validation SHA-256: $validationHash"
    Write-Host "[administering-rc-proof] Owner review: $ownerReviewPath"
    Write-Host "[administering-rc-proof] Owner review SHA-256: $ownerReviewHash"
    Write-Host "[administering-rc-proof] Final seal: $finalSealPath"
    Write-Host "[administering-rc-proof] Final seal SHA-256: $finalSealHash"
    Write-Host "[administering-rc-proof] Final seal validation: $finalSealValidationPath"
    Write-Host "[administering-rc-proof] Final seal validation SHA-256: $finalSealValidationHash"
    Write-Host "[administering-rc-proof] RC status: $statusPath"
    Write-Host "[administering-rc-proof] RC status SHA-256: $statusHash"
    Write-Host "[administering-rc-proof] RC status summary: $statusSummaryPath"
    Write-Host "[administering-rc-proof] RC status summary SHA-256: $statusSummaryHash"
    Write-Host "[administering-rc-proof] RC receipt: $receiptPath"
    Write-Host "[administering-rc-proof] RC receipt SHA-256: $receiptHash"
    Write-Host "[administering-rc-proof] RC receipt text: $receiptTextPath"
    Write-Host "[administering-rc-proof] RC receipt text SHA-256: $receiptTextHash"
    Write-Host "[administering-rc-proof] RC receipt validation: $receiptValidationPath"
    Write-Host "[administering-rc-proof] RC receipt validation SHA-256: $receiptValidationHash"
    Write-Host "[administering-rc-proof] Final status validation: $finalStatusValidationPath"
    Write-Host "[administering-rc-proof] Handoff index: $handoffIndexPath"
    Write-Host "[administering-rc-proof] Handoff index text: $handoffIndexTextPath"
    Write-Host "[administering-rc-proof] Handoff index validation: $handoffIndexValidationPath"
    Write-Host "[administering-rc-proof] Terminal status: $terminalStatusPath"
    Write-Host "[administering-rc-proof] Terminal status summary: $terminalStatusSummaryPath"
    Write-Host "[administering-rc-proof] Terminal status validation: $terminalStatusValidationPath"
    Write-Host "[administering-rc-proof] Handoff bundle: $handoffBundlePath"
    Write-Host "[administering-rc-proof] Handoff bundle text: $handoffBundleTextPath"
    Write-Host "[administering-rc-proof] Handoff bundle validation: $handoffBundleValidationPath"
    Write-Host "[administering-rc-proof] Bundle-aware status: $bundleStatusPath"
    Write-Host "[administering-rc-proof] Bundle-aware status summary: $bundleStatusSummaryPath"
    Write-Host "[administering-rc-proof] 3RC acceptance: $acceptancePath"
    Write-Host "[administering-rc-proof] 3RC acceptance text: $acceptanceTextPath"
} finally {
    Pop-Location
}
