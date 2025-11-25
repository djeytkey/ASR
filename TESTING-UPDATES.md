# Testing WordPress Updates from GitHub

This guide will walk you through testing the WordPress auto-update functionality from your private GitHub repository.

## Prerequisites

1. ✅ Version updated to 1.0.2 in the plugin
2. ✅ GitHub repository is set up (private)
3. ✅ GitHub Actions workflow is configured
4. ✅ WordPress site with the plugin installed

## Step-by-Step Testing Guide

### Step 1: Configure GitHub Settings in WordPress

1. **Log in to WordPress Admin**
   - Go to your WordPress admin dashboard
   - Navigate to **Oud Sales Report → Settings** (or the Arabic equivalent)

2. **Enter GitHub Information**
   - **Repository Owner**: Your GitHub username or organization name
   - **Repository Name**: The name of your repository (e.g., `almokhlif-oud-sales-report`)
   - **Access Token**: Your GitHub Personal Access Token (required for private repos)
     - To create a token: https://github.com/settings/tokens
     - Required scopes: `repo` (for private repositories)

3. **Save Settings**
   - Click "حفظ إعدادات GitHub" (Save GitHub Settings)
   - You should see a success message

### Step 2: Create a GitHub Release

1. **Push Current Code to GitHub**
   ```bash
   git add .
   git commit -m "Update to version 1.0.2"
   git push origin main
   ```

2. **Create a Release on GitHub**
   - Go to your GitHub repository
   - Click on **Releases** (right sidebar)
   - Click **Create a new release**
   - **Tag version**: `v1.0.2` (must start with 'v')
   - **Release title**: `Version 1.0.2`
   - **Description**: Add release notes (optional)
   - Click **Publish release**

   **Important**: The tag version must match the plugin version (with 'v' prefix). For example:
   - Plugin version: `1.0.2` → Tag: `v1.0.2`
   - Plugin version: `1.0.3` → Tag: `v1.0.3`

### Step 3: Test Update Detection

1. **Clear WordPress Cache**
   - Go to WordPress Admin → Plugins
   - Or use a caching plugin to clear cache

2. **Check for Updates**
   - Go to **Dashboard → Updates** (or **Plugins → Installed Plugins**)
   - WordPress should automatically check for updates
   - You should see "Almokhlif Oud Sales Report" in the update list if version 1.0.2 is newer than your installed version

3. **Manual Update Check** (if needed)
   - Go to **Dashboard → Updates**
   - Click **Check Again** button
   - Wait a few seconds for WordPress to check

### Step 4: Install the Update

1. **Update the Plugin**
   - In the Updates page, you should see your plugin listed
   - Check the box next to "Almokhlif Oud Sales Report"
   - Click **Update Plugins**

2. **Verify Update**
   - After update completes, go to **Plugins → Installed Plugins**
   - Check that the version shows as **1.0.2**
   - Verify the plugin is still active and working

### Step 5: Test Future Updates

To test the update process again:

1. **Update Version in Code**
   - Edit `almokhlif-oud-sales-report.php`
   - Change version to `1.0.3` (in both header and constant)
   - Commit and push to GitHub

2. **Create New Release**
   - Create a new release with tag `v1.0.3`
   - Publish the release

3. **Check for Update in WordPress**
   - WordPress will check for updates automatically (every 12 hours)
   - Or manually check in Dashboard → Updates
   - The update should appear and be installable

## Troubleshooting

### Update Not Showing Up

1. **Check GitHub Settings**
   - Verify owner and repo name are correct
   - Ensure access token is valid and has `repo` scope
   - Check that the release exists on GitHub

2. **Check Version Numbers**
   - Plugin version must be lower than release version
   - Release tag must be `v` + version number (e.g., `v1.0.2`)

3. **Clear Cache**
   - Delete transient: `almokhlif_oud_sr_latest_release`
   - You can do this by temporarily adding this code:
     ```php
     delete_transient( 'almokhlif_oud_sr_latest_release' );
     ```
   - Or wait 12 hours for cache to expire

4. **Check WordPress Debug Log**
   - Enable `WP_DEBUG` in `wp-config.php`
   - Check for errors related to the update checker

5. **Verify GitHub API Access**
   - Test the API URL manually:
     ```
     https://api.github.com/repos/YOUR_OWNER/YOUR_REPO/releases/latest
     ```
   - For private repos, add `?access_token=YOUR_TOKEN` to the URL
   - Should return JSON with release information

### Download Fails

1. **Check Access Token**
   - Token must have `repo` scope for private repositories
   - Token should not be expired

2. **Check File Permissions**
   - WordPress needs write permissions to `/wp-content/upgrade/`
   - Check server file permissions

3. **Check Server Requirements**
   - Ensure `allow_url_fopen` is enabled
   - Or ensure cURL is available

### Update Installs But Doesn't Activate

- This is normal behavior
- WordPress will install the update
- You may need to reactivate the plugin manually if it deactivates

## Automated Testing with GitHub Actions

The GitHub Actions workflow can also create releases automatically. To set this up:

1. **Update `.github/workflows/deploy.yml`** to create a release when version changes
2. **Add a step** that:
   - Detects version change
   - Creates a GitHub release
   - Tags the commit

## Quick Test Checklist

- [ ] GitHub settings configured in WordPress
- [ ] Version 1.0.2 committed and pushed
- [ ] GitHub release v1.0.2 created
- [ ] WordPress detects the update
- [ ] Update installs successfully
- [ ] Plugin version shows 1.0.2 after update
- [ ] Plugin functionality works after update

## Notes

- WordPress checks for updates every 12 hours automatically
- The update checker caches results for 12 hours
- For private repositories, a GitHub Personal Access Token is required
- Release tags must follow the format: `v{VERSION}` (e.g., `v1.0.2`)

