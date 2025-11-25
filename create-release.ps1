# PowerShell script to create a GitHub release manually

param(
    [string]$Version = "",
    [string]$Owner = "",
    [string]$Repo = "",
    [string]$Token = ""
)

Write-Host "=== Create GitHub Release ===" -ForegroundColor Cyan
Write-Host ""

# Get version from plugin file if not provided
if ([string]::IsNullOrWhiteSpace($Version)) {
    $pluginFile = "almokhlif-oud-sales-report.php"
    if (Test-Path $pluginFile) {
        $content = Get-Content $pluginFile -Raw
        if ($content -match "Version:\s+(\d+\.\d+\.\d+)") {
            $Version = $matches[1]
            Write-Host "Detected version from plugin file: $Version" -ForegroundColor Green
        }
    }
    
    if ([string]::IsNullOrWhiteSpace($Version)) {
        $Version = Read-Host "Enter version number (e.g., 1.0.2)"
    }
}

# Get GitHub info if not provided
if ([string]::IsNullOrWhiteSpace($Owner)) {
    $Owner = Read-Host "Enter GitHub owner/username"
}

if ([string]::IsNullOrWhiteSpace($Repo)) {
    $Repo = Read-Host "Enter repository name"
}

if ([string]::IsNullOrWhiteSpace($Token)) {
    $useToken = Read-Host "Do you want to use a GitHub token? (y/n)"
    if ($useToken -eq "y" -or $useToken -eq "Y") {
        $Token = Read-Host "Enter GitHub Personal Access Token"
    }
}

$Tag = "v$Version"
$ReleaseName = "Version $Version"

Write-Host ""
Write-Host "Release Information:" -ForegroundColor Yellow
Write-Host "  Tag: $Tag"
Write-Host "  Version: $Version"
Write-Host "  Owner: $Owner"
Write-Host "  Repo: $Repo"
Write-Host ""

$confirm = Read-Host "Create release? (y/n)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "Cancelled." -ForegroundColor Yellow
    exit
}

# Create tag locally
Write-Host "Creating git tag..." -ForegroundColor Yellow
git tag -a $Tag -m "Version $Version"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error creating tag. It might already exist." -ForegroundColor Red
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        exit
    }
}

# Push tag
Write-Host "Pushing tag to GitHub..." -ForegroundColor Yellow
git push origin $Tag
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error pushing tag." -ForegroundColor Red
    exit
}

# Create release using GitHub API
Write-Host "Creating GitHub release..." -ForegroundColor Yellow

$headers = @{
    "Accept" = "application/vnd.github.v3+json"
    "User-Agent" = "PowerShell-Release-Script"
}

if (-not [string]::IsNullOrWhiteSpace($Token)) {
    $headers["Authorization"] = "token $Token"
}

$body = @{
    tag_name = $Tag
    name = $ReleaseName
    body = "Version $Version`n`n### Changes`n- Version updated to $Version"
    draft = $false
    prerelease = $false
} | ConvertTo-Json

$uri = "https://api.github.com/repos/$Owner/$Repo/releases"

try {
    $response = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body -ContentType "application/json"
    Write-Host ""
    Write-Host "✓ Release created successfully!" -ForegroundColor Green
    Write-Host "  URL: $($response.html_url)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "WordPress should now detect this update!" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "Error creating release:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "You can create the release manually at:" -ForegroundColor Yellow
    Write-Host "https://github.com/$Owner/$Repo/releases/new" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Tag $Tag has been pushed. Use it to create the release." -ForegroundColor Yellow
}

