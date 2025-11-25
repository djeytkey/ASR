# PowerShell script to help set up GitHub repository and initial push

Write-Host "=== Almokhlif Oud Sales Report - GitHub Setup ===" -ForegroundColor Cyan
Write-Host ""

# Check if git is initialized
if (-not (Test-Path ".git")) {
    Write-Host "Initializing git repository..." -ForegroundColor Yellow
    git init
}

# Check if remote exists
$remoteUrl = git remote get-url origin 2>$null
if ($remoteUrl) {
    Write-Host "GitHub remote already configured: $remoteUrl" -ForegroundColor Green
    Write-Host ""
    $change = Read-Host "Do you want to change it? (y/n)"
    if ($change -eq "y" -or $change -eq "Y") {
        $repoUrl = Read-Host "Enter GitHub repository URL"
        git remote set-url origin $repoUrl
    }
} else {
    $repoUrl = Read-Host "Enter your GitHub repository URL"
    git remote add origin $repoUrl
}

# Set branch to main
git branch -M main

# Add all files
Write-Host "Adding files to git..." -ForegroundColor Yellow
git add .

# Check if there are changes to commit
$status = git status --porcelain
if ($status) {
    $commitMsg = Read-Host "Enter commit message (or press Enter for default)"
    if ([string]::IsNullOrWhiteSpace($commitMsg)) {
        $commitMsg = "Initial commit with CI/CD setup"
    }
    git commit -m $commitMsg
} else {
    Write-Host "No changes to commit." -ForegroundColor Yellow
}

# Push to GitHub
Write-Host ""
$push = Read-Host "Push to GitHub? (y/n)"
if ($push -eq "y" -or $push -eq "Y") {
    git push -u origin main
    Write-Host ""
    Write-Host "✓ Code pushed to GitHub!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Go to your GitHub repository"
    Write-Host "2. Settings → Secrets and variables → Actions"
    Write-Host "3. Add the required secrets (see DEPLOYMENT.md)"
    Write-Host "4. Your next push to main will automatically deploy!"
} else {
    Write-Host "Skipped push. Run 'git push -u origin main' when ready." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Setup complete!" -ForegroundColor Green

