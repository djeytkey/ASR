# How to Find and Create GitHub Releases

## Quick Answer: Direct Links

If you can't find the Releases section, use these direct links:

### Create a New Release
```
https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/releases/new
```
Replace `YOUR_USERNAME` and `YOUR_REPO_NAME` with your actual values.

### View All Releases
```
https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/releases
```

## Where to Look in GitHub

### Method 1: Right Sidebar
1. Go to your repository homepage
2. Look at the **right sidebar** (scroll down a bit)
3. Find **"Releases"** link (usually below "About" section)
4. Click it → Then click **"Create a new release"**

### Method 2: Top Navigation Bar
1. Go to your repository
2. Look at the top navigation (Code, Issues, Pull requests, etc.)
3. Click **"Releases"** tab
4. Click **"Create a new release"** button

### Method 3: Tags Page
1. Go to your repository
2. Click on **"Tags"** (in the code view, or releases page)
3. You'll see a **"Releases"** link there
4. Click **"Create a new release"**

## Visual Guide

```
┌─────────────────────────────────────────┐
│  [Code] [Issues] [PR] [Actions] [Releases] ← Look here!
└─────────────────────────────────────────┘

┌─────────────────┐  ┌──────────────────┐
│                 │  │  About           │
│   Code Files    │  │  Releases  ←───  │  ← Or here!
│                 │  │  Packages        │
│                 │  │  ...              │
└─────────────────┘  └──────────────────┘
```

## If Releases Section is Completely Missing

This can happen if:
1. **Repository is brand new** - Releases section appears after first release
2. **Repository settings** - Check Settings → General
3. **Permissions** - Make sure you have write/admin access

**Solution**: Use the direct URL method above - it always works!

## Easiest Method: Use the Script

I've created a PowerShell script that does everything for you:

```powershell
.\create-release.ps1
```

Just run it and follow the prompts. It will:
1. Detect version from your plugin file
2. Create the git tag
3. Push the tag
4. Create the GitHub release automatically

## Automatic Method: GitHub Actions

The workflow I created will automatically create releases when you push version changes!

Just:
1. Update version in `almokhlif-oud-sales-report.php`
2. Commit and push
3. The workflow creates the release automatically

Check `.github/workflows/create-release.yml` for details.

## Still Stuck?

1. **Use GitHub CLI** (if installed):
   ```bash
   gh release create v1.0.2 --title "Version 1.0.2"
   ```

2. **Use Git commands**:
   ```bash
   git tag -a v1.0.2 -m "Version 1.0.2"
   git push origin v1.0.2
   ```
   Then go to GitHub and create release from the tag.

3. **Contact me** with your repository URL and I can help you find it!

