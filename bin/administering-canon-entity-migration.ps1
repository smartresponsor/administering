param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$entityRoot = Join-Path $ProjectRoot 'src\Entity'

if (-not (Test-Path -LiteralPath $entityRoot -PathType Container)) {
    throw "Entity root not found: $entityRoot"
}

$mapping = [ordered]@{}
$entityFiles = Get-ChildItem -LiteralPath $entityRoot -Recurse -File -Filter '*.php' | Sort-Object FullName

foreach ($file in $entityFiles) {
    $content = Get-Content -LiteralPath $file.FullName -Raw
    $match = [regex]::Match($content, '(?m)^(?:(?:final|abstract|readonly)\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)')
    if (-not $match.Success) {
        continue
    }

    $oldName = $match.Groups[1].Value
    $newName = $oldName
    if (-not $newName.StartsWith('Administration', [System.StringComparison]::Ordinal)) {
        $newName = 'Administration' + $newName
    }
    if (-not $newName.EndsWith('Entity', [System.StringComparison]::Ordinal)) {
        $newName += 'Entity'
    }

    if ($oldName -ne $newName) {
        $mapping[$oldName] = $newName
    }
}

Write-Host ("Entity identity mappings: {0}" -f $mapping.Count)
foreach ($entry in $mapping.GetEnumerator()) {
    Write-Host ("  {0} -> {1}" -f $entry.Key, $entry.Value)
}

if ($DryRun -or 0 -eq $mapping.Count) {
    exit 0
}

$excludedSegments = @(
    '\\.git\\',
    '\\vendor\\',
    '\\var\\',
    '\\node_modules\\',
    '\\.gating\\'
)

$textExtensions = @(
    '.php', '.yaml', '.yml', '.xml', '.adoc', '.md', '.json', '.neon', '.dist', '.txt', '.ps1'
)

$files = Get-ChildItem -LiteralPath $ProjectRoot -Recurse -File | Where-Object {
    $fullName = $_.FullName
    foreach ($segment in $excludedSegments) {
        if ($fullName -match $segment) {
            return $false
        }
    }

    return $textExtensions -contains $_.Extension.ToLowerInvariant()
}

foreach ($file in $files) {
    $content = Get-Content -LiteralPath $file.FullName -Raw
    $updated = $content

    foreach ($entry in $mapping.GetEnumerator()) {
        $pattern = '(?<![A-Za-z0-9_])' + [regex]::Escape($entry.Key) + '(?![A-Za-z0-9_])'
        $updated = [regex]::Replace($updated, $pattern, $entry.Value)
    }

    if ($updated -ne $content) {
        [System.IO.File]::WriteAllText($file.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
    }
}

foreach ($file in $entityFiles) {
    if (-not (Test-Path -LiteralPath $file.FullName -PathType Leaf)) {
        continue
    }

    $content = Get-Content -LiteralPath $file.FullName -Raw
    $match = [regex]::Match($content, '(?m)^(?:(?:final|abstract|readonly)\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)')
    if (-not $match.Success) {
        continue
    }

    $className = $match.Groups[1].Value
    $expectedName = $className + '.php'
    if ($file.Name -eq $expectedName) {
        continue
    }

    $destination = Join-Path $file.DirectoryName $expectedName
    if (Test-Path -LiteralPath $destination) {
        throw "Cannot rename $($file.FullName): destination exists: $destination"
    }

    Move-Item -LiteralPath $file.FullName -Destination $destination
}

Write-Host 'Administering Entity identity migration completed.'

