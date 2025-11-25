#!/bin/bash

# Script to help set up GitHub repository and initial push

echo "=== Almokhlif Oud Sales Report - GitHub Setup ==="
echo ""

# Check if git is initialized
if [ ! -d ".git" ]; then
    echo "Initializing git repository..."
    git init
fi

# Check if remote exists
if git remote get-url origin > /dev/null 2>&1; then
    echo "GitHub remote already configured:"
    git remote get-url origin
    echo ""
    read -p "Do you want to change it? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Enter GitHub repository URL: " repo_url
        git remote set-url origin "$repo_url"
    fi
else
    read -p "Enter your GitHub repository URL: " repo_url
    git remote add origin "$repo_url"
fi

# Set branch to main
git branch -M main

# Add all files
echo "Adding files to git..."
git add .

# Check if there are changes to commit
if git diff --staged --quiet; then
    echo "No changes to commit."
else
    read -p "Enter commit message (or press Enter for default): " commit_msg
    if [ -z "$commit_msg" ]; then
        commit_msg="Initial commit with CI/CD setup"
    fi
    git commit -m "$commit_msg"
fi

# Push to GitHub
echo ""
read -p "Push to GitHub? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push -u origin main
    echo ""
    echo "✓ Code pushed to GitHub!"
    echo ""
    echo "Next steps:"
    echo "1. Go to your GitHub repository"
    echo "2. Settings → Secrets and variables → Actions"
    echo "3. Add the required secrets (see DEPLOYMENT.md)"
    echo "4. Your next push to main will automatically deploy!"
else
    echo "Skipped push. Run 'git push -u origin main' when ready."
fi

echo ""
echo "Setup complete!"

