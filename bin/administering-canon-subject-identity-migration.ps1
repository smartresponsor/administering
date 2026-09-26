param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$srcRoot = Join-Path $ProjectRoot 'src'
$mapping = [ordered]@{}

$sourceFiles = Get-ChildItem -LiteralPath $srcRoot -Recurse -File -Filter '*.php' | Sort-Object FullName

foreach ($file in $sourceFiles) {
    $relative = $file.FullName.Substring($ProjectRoot.Length + 1).Replace('\', '/')
    $parts = $relative.Split('/')

    if ($parts.Count -lt 3) {
        continue
    }

    if ($parts[1] -eq 'DependencyInjection') {
        continue
    }

    if ($relative -match '^src/Rule/Canon/Canon\d{3}[A-Z][A-Za-z0-9]*Rule\.php$') {
        continue
    }

    $content = Get-Content -LiteralPath $file.FullName -Raw
    $match = [regex]::Match(
        $content,
        '(?m)^(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)'
    )
    if (-not $match.Success) {
        continue
    }

    $oldName = $match.Groups[1].Value
    if ($oldName.StartsWith('Administration', [System.StringComparison]::Ordinal)) {
        continue
    }

    $index = $oldName.IndexOf('Administration', [System.StringComparison]::Ordinal)
    if ($index -ge 0) {
        $without = $oldName.Remove($index, 'Administration'.Length)
        $newName = 'Administration' + $without
    } else {
        $newName = 'Administration' + $oldName
    }

    if ($mapping.Contains($oldName)) {
        throw "Duplicate declaration mapping for $oldName"
    }

    $mapping[$oldName] = $newName
}

Write-Host ("Subject identity mappings: {0}" -f $mapping.Count)
foreach ($entry in $mapping.GetEnumerator()) {
    Write-Host ("  {0} -> {1}" -f $entry.Key, $entry.Value)
}

if ($DryRun) {
    exit 0
}

$excludedSegments = @(
    '\.git\',
    '\vendor\',
    '\var\',
    '\node_modules\',
    '\.gating\'
)

$textExtensions = @(
    '.php', '.yaml', '.yml', '.xml', '.adoc', '.md', '.json', '.neon', '.dist', '.txt', '.ps1'
)

$files = Get-ChildItem -LiteralPath $ProjectRoot -Recurse -File | Where-Object {
    $fullName = $_.FullName
    foreach ($segment in $excludedSegments) {
        if ($fullName.Contains($segment)) {
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

foreach ($file in $sourceFiles) {
    if (-not (Test-Path -LiteralPath $file.FullName -PathType Leaf)) {
        continue
    }

    $content = Get-Content -LiteralPath $file.FullName -Raw
    $match = [regex]::Match(
        $content,
        '(?m)^(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)'
    )
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
        $sourceHash = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash
        $destinationHash = (Get-FileHash -LiteralPath $destination -Algorithm SHA256).Hash
        if ($sourceHash -eq $destinationHash) {
            Write-Host ("Removing exact duplicate source: {0}" -f $file.FullName)
            Remove-Item -LiteralPath $file.FullName -Force
            continue
        }

        throw "Cannot rename $($file.FullName): destination exists with different content: $destination"
    }

    Move-Item -LiteralPath $file.FullName -Destination $destination
}

Write-Host 'Administering subject identity migration completed.'

