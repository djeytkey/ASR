# Quick Test Steps - WordPress Update from GitHub

## Current Status
✅ Version updated to **1.0.2**  
✅ WordPress update checker integrated  
✅ GitHub settings page added  

## Testing Steps

### 1. Configure GitHub in WordPress (First Time Only)

1. Go to **WordPress Admin → Oud Sales Report → Settings**
2. Scroll to **"إعدادات التحديث من GitHub"** section
3. Fill in:
   - **Owner**: Your GitHub username
   - **Repository**: Repository name (e.g., `almokhlif-oud-sales-report`)
   - **Token**: GitHub Personal Access Token (create at https://github.com/settings/tokens)
     - Required scope: `repo` (for private repositories)
4. Click **"حفظ إعدادات GitHub"**

### 2. Push Code to GitHub

```bash
git add .
git commit -m "Update to version 1.0.2"
git push origin main
```

### 3. Create GitHub Release

1. Go to your GitHub repository
2. Click **Releases** → **Create a new release**
3. **Tag version**: `v1.0.2` (must start with 'v')
4. **Release title**: `Version 1.0.2`
5. Click **Publish release**

### 4. Check for Update in WordPress

1. Go to **WordPress Admin → Dashboard → Updates**
2. Click **"Check Again"** button
3. You should see "Almokhlif Oud Sales Report" in the update list
4. Check the box and click **"Update Plugins"**

### 5. Verify Update

1. Go to **Plugins → Installed Plugins**
2. Verify version shows **1.0.2**
3. Test plugin functionality

## Troubleshooting

**Update not showing?**
- Wait 12 hours (cache) or clear transient manually
- Verify GitHub settings are correct
- Check that release tag matches version (v1.0.2)
- Ensure access token has `repo` scope

**Need to clear cache immediately?**
Add this temporarily to your theme's `functions.php`:
```php
delete_transient( 'almokhlif_oud_sr_latest_release' );
```

## Next Update

For version 1.0.3:
1. Update version in `almokhlif-oud-sales-report.php` (line 6 and 22)
2. Commit and push
3. Create release `v1.0.3`
4. WordPress will detect and allow update

