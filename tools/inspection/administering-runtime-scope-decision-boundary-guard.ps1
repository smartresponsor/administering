param(
    [string] $RootPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

$ScriptPath = Join-Path $RootPath 'tools\inspection\administering-runtime-scope-decision-boundary-guard.php'
php $ScriptPath
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
