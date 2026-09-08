<#
.SYNOPSIS
    Commit, tag, push and release Ofnoa Marquee.

.DESCRIPTION
    One command to get local work onto GitHub:
      - reads the version from the plugin header and checks it against OMQ_VERSION
      - optionally commits everything with a message you pass
      - optionally rebuilds ofnoa-marquee.zip (correctly nested, no dev files)
      - pushes the branch
      - optionally creates and pushes the matching vX.Y.Z tag
      - optionally publishes a GitHub release with the ZIP attached (needs gh)

.EXAMPLE
    .\push.ps1
    Push whatever is already committed.

.EXAMPLE
    .\push.ps1 -Message "fix: separator spacing on mobile"
    Commit everything with that message, then push.

.EXAMPLE
    .\push.ps1 -Tag -Zip -Release
    Push, build the ZIP, tag v<version>, and publish the release with the ZIP.
#>

#Requires -Version 5.1
[CmdletBinding()]
param(
    # Commit all pending changes with this message before pushing.
    [string] $Message,

    # Create and push the tag v<version> read from the plugin header.
    [switch] $Tag,

    # Rebuild ofnoa-marquee.zip before pushing.
    [switch] $Zip,

    # Publish a GitHub release for the tag with the ZIP attached (requires gh).
    [switch] $Release,

    [string] $Remote = 'origin',
    [string] $Branch = 'main'
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

function Step($m) { Write-Host ""; Write-Host "==> $m" -ForegroundColor Cyan }
function Good($m) { Write-Host "    $m" -ForegroundColor Green }
function Note($m) { Write-Host "    $m" -ForegroundColor DarkGray }
function Warn($m) { Write-Host "    $m" -ForegroundColor Yellow }
function Die($m)  { Write-Host ""; Write-Host "FAILED: $m" -ForegroundColor Red; exit 1 }

function Have($name) { $null -ne (Get-Command $name -ErrorAction SilentlyContinue) }

# ---------------------------------------------------------------- sanity

if (-not (Have git)) { Die "git is not on PATH." }
if (-not (Test-Path .\ofnoa-marquee.php)) { Die "Run this from the plugin folder (ofnoa-marquee.php not found)." }

git rev-parse --is-inside-work-tree *> $null
if ($LASTEXITCODE -ne 0) { Die "This folder is not a git repository." }

# ---------------------------------------------------------------- version

Step "Reading version"

$headerHit = Select-String -Path .\ofnoa-marquee.php -Pattern '^\s*\*\s*Version:\s*([0-9][0-9.]*)' | Select-Object -First 1
if (-not $headerHit) { Die "No 'Version:' line in the plugin header." }
$version = $headerHit.Matches[0].Groups[1].Value

$constHit = Select-String -Path .\ofnoa-marquee.php -Pattern "OMQ_VERSION',\s*'([0-9][0-9.]*)'" | Select-Object -First 1
if (-not $constHit) { Die "OMQ_VERSION constant not found." }
$constVersion = $constHit.Matches[0].Groups[1].Value

if ($version -ne $constVersion) {
    Die "Version mismatch: header says $version but OMQ_VERSION is $constVersion. Fix both, then run again."
}

$stableHit = Select-String -Path .\readme.txt -Pattern '^Stable tag:\s*([0-9][0-9.]*)' -ErrorAction SilentlyContinue | Select-Object -First 1
if ($stableHit -and $stableHit.Matches[0].Groups[1].Value -ne $version) {
    Warn "readme.txt Stable tag is $($stableHit.Matches[0].Groups[1].Value), plugin is $version."
}

Good "version $version"

# ---------------------------------------------------------------- branch

$current = (git rev-parse --abbrev-ref HEAD).Trim()
if ($current -ne $Branch) {
    Warn "You are on '$current', not '$Branch'. Pushing '$current'."
    $Branch = $current
}

# ---------------------------------------------------------------- zip

if ($Zip) {
    Step "Building ofnoa-marquee.zip"

    $staging = Join-Path $env:TEMP ("omq-build-" + [guid]::NewGuid().ToString('N'))
    $target  = Join-Path $staging 'ofnoa-marquee'
    New-Item -ItemType Directory -Path $target -Force | Out-Null

    $excludeDirs  = @('.git', '.github', 'node_modules', 'build', 'Claude outputs')
    $excludeFiles = @('.gitignore', 'ofnoa-marquee.zip', 'push.ps1', 'push.bat')

    Get-ChildItem -Path . -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring((Get-Location).Path.Length).TrimStart('\')
        $top = $rel.Split('\')[0]
        if ($excludeDirs -contains $top) { return }
        if ($excludeFiles -contains $_.Name) { return }

        $dest = Join-Path $target $rel
        New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
        Copy-Item $_.FullName $dest
    }

    if (Test-Path .\ofnoa-marquee.zip) { Remove-Item .\ofnoa-marquee.zip -Force }

    # .NET rather than Compress-Archive: it writes forward-slash entry names,
    # which is what WordPress' unzip expects.
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zipPath = Join-Path (Get-Location).Path 'ofnoa-marquee.zip'
    [System.IO.Compression.ZipFile]::CreateFromDirectory(
        $staging,
        $zipPath,
        [System.IO.Compression.CompressionLevel]::Optimal,
        $false
    )

    Remove-Item $staging -Recurse -Force

    $kb = [math]::Round((Get-Item .\ofnoa-marquee.zip).Length / 1KB, 1)
    Good "ofnoa-marquee.zip ($kb KB)"
}

# ---------------------------------------------------------------- commit

$dirty = git status --porcelain

if ($Message) {
    if (-not $dirty) {
        Note "Nothing to commit, working tree is clean."
    } else {
        Step "Committing"
        git add -A
        git commit -m $Message
        if ($LASTEXITCODE -ne 0) { Die "git commit failed." }
        Good (git log --oneline -1)
    }
}
elseif ($dirty) {
    Write-Host ""
    Warn "Uncommitted changes:"
    git status --short
    Die "Commit them first, or pass -Message ""your message""."
}

# ---------------------------------------------------------------- what is going out

Step "Pushing $Branch to $Remote"

git rev-parse --verify --quiet "refs/remotes/$Remote/$Branch" *> $null
if ($LASTEXITCODE -eq 0) {
    $ahead = git log --oneline "$Remote/$Branch..HEAD"
    if ($ahead) { $ahead | ForEach-Object { Note $_ } } else { Note "Nothing new — remote is already up to date." }
}

$pushOutput = & git push $Remote $Branch 2>&1
$pushOutput | ForEach-Object { Note $_ }

if ($LASTEXITCODE -ne 0) {
    if ($pushOutput -match 'workflow') {
        Write-Host ""
        Warn "GitHub refused the push because your token lacks the 'workflow' scope"
        Warn "(it is needed to create or change files under .github/workflows/)."
        Warn "Fix it once with:   gh auth refresh -h github.com -s workflow"
    }
    elseif ($pushOutput -match 'could not read Username|Authentication failed') {
        Write-Host ""
        Warn "No credentials for GitHub. Sign in with:   gh auth login"
    }
    Die "git push failed."
}
Good "branch pushed"

# ---------------------------------------------------------------- tag

if ($Tag) {
    Step "Tagging v$version"

    $existsLocal = (git tag --list "v$version")
    if ($existsLocal) {
        Note "Local tag v$version already exists."
    } else {
        git tag "v$version"
        if ($LASTEXITCODE -ne 0) { Die "Could not create the tag." }
        Good "created v$version"
    }

    & git push $Remote "v$version" 2>&1 | ForEach-Object { Note $_ }
    if ($LASTEXITCODE -ne 0) { Die "Could not push the tag. If it already exists on GitHub, delete it first: git push --delete $Remote v$version" }
    Good "tag pushed"
}

# ---------------------------------------------------------------- release

if ($Release) {
    Step "Publishing the GitHub release"

    if (-not (Have gh)) { Die "gh (GitHub CLI) is not installed — create the release manually, or install it from https://cli.github.com" }
    if (-not (Test-Path .\ofnoa-marquee.zip)) { Die "ofnoa-marquee.zip not found. Run again with -Zip." }

    gh release view "v$version" *> $null
    if ($LASTEXITCODE -eq 0) {
        Note "Release v$version already exists — uploading the ZIP over it."
        gh release upload "v$version" .\ofnoa-marquee.zip --clobber
    } else {
        gh release create "v$version" .\ofnoa-marquee.zip --title "v$version" --generate-notes
    }
    if ($LASTEXITCODE -ne 0) { Die "gh release failed." }
    Good "release v$version is live"
}

Write-Host ""
Write-Host "Done — Ofnoa Marquee $version is on GitHub." -ForegroundColor Green
Write-Host ""
