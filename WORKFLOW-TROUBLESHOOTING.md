# GitHub Actions Workflow Troubleshooting

## Common Issues and Solutions

### Issue: "Create Release" Workflow Failing

#### Problem 1: Permissions Error
**Error**: `Resource not accessible by integration` or `403 Forbidden`

**Solution**: 
- The workflow needs `contents: write` permission
- This is now included in the workflow file
- If still failing, check repository Settings → Actions → General → Workflow permissions
- Ensure "Read and write permissions" is selected

#### Problem 2: Version Extraction Failing
**Error**: `Error: Could not extract version from plugin file`

**Solution**:
- Check that `almokhlif-oud-sales-report.php` exists in the root
- Verify the version line format: `Version: 1.0.2` (with space after colon)
- The workflow uses `sed` which is more compatible than `grep -oP`

#### Problem 3: Tag Already Exists
**Error**: Tag push fails because tag exists

**Solution**:
- The workflow checks if tag exists and skips creation if it does
- If you need to recreate, delete the tag first:
  ```bash
  git tag -d v1.0.2
  git push origin :refs/tags/v1.0.2
  ```

#### Problem 4: Git Push Fails
**Error**: `Permission denied` or authentication errors

**Solution**:
- The workflow now uses `GITHUB_TOKEN` for checkout
- Ensure the token has proper permissions
- For private repos, this should work automatically with `GITHUB_TOKEN`

### Issue: Workflow Not Triggering

#### Check Trigger Conditions
The workflow triggers on:
- Push to `main` or `master` branch
- AND file `almokhlif-oud-sales-report.php` was changed

**If not triggering**:
1. Check you're pushing to the correct branch
2. Verify the plugin file was actually modified
3. Check GitHub Actions tab for any workflow runs

### Issue: Release Created But Not Showing in WordPress

#### Check WordPress Settings
1. Go to WordPress Admin → Oud Sales Report → Settings
2. Verify GitHub settings are configured:
   - Owner: Correct GitHub username
   - Repository: Correct repository name
   - Token: Valid GitHub Personal Access Token with `repo` scope

#### Check Release Format
- Tag must be: `v1.0.2` (with 'v' prefix)
- Version in plugin must be: `1.0.2` (without 'v')
- Release must be published (not draft)

#### Clear WordPress Cache
```php
// Add temporarily to functions.php
delete_transient( 'almokhlif_oud_sr_latest_release' );
```

### Debugging Steps

1. **Check Workflow Logs**
   - Go to GitHub → Actions tab
   - Click on the failed workflow run
   - Expand each step to see detailed logs

2. **Test Version Extraction Locally**
   ```bash
   grep "Version:" almokhlif-oud-sales-report.php
   ```

3. **Test Tag Creation Locally**
   ```bash
   git tag -a v1.0.2 -m "Test"
   git push origin v1.0.2
   ```

4. **Verify GitHub Token Permissions**
   - Go to https://github.com/settings/tokens
   - Check your token has `repo` scope
   - For private repositories, this is required

### Manual Workaround

If the workflow keeps failing, you can create releases manually:

1. **Using PowerShell Script**:
   ```powershell
   .\create-release.ps1
   ```

2. **Using GitHub Website**:
   - Go to: `https://github.com/YOUR_USERNAME/YOUR_REPO/releases/new`
   - Create tag: `v1.0.2`
   - Create release

3. **Using Git Commands**:
   ```bash
   git tag -a v1.0.2 -m "Version 1.0.2"
   git push origin v1.0.2
   ```
   Then create release from the tag on GitHub.

### Recent Fixes Applied

✅ Replaced GitHub CLI installation with reliable action (`softprops/action-gh-release@v1`)
✅ Fixed version extraction to use `sed` instead of `grep -oP` (better compatibility)
✅ Added `contents: write` permission
✅ Added proper token authentication for checkout
✅ Added error handling for version extraction

### Still Having Issues?

1. Check the exact error message in GitHub Actions logs
2. Verify all prerequisites are met
3. Try the manual workaround methods above
4. Check that your repository allows GitHub Actions (Settings → Actions → General)

