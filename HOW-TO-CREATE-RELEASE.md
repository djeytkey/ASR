# How to Create a GitHub Release

## Where to Find Releases

The "Releases" section can be found in different places depending on your GitHub repository view:

### Method 1: Right Sidebar (Most Common)
1. Go to your GitHub repository homepage
2. Look at the **right sidebar** (below the "About" section)
3. You should see a **"Releases"** link
4. Click on it, then click **"Create a new release"**

### Method 2: Direct URL
If you can't find it in the sidebar, go directly to:
```
https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/releases
```
Replace `YOUR_USERNAME` and `YOUR_REPO_NAME` with your actual values.

### Method 3: Tags Page
1. Go to your repository
2. Click on **"Tags"** (usually in the code tab, or in the releases section)
3. Click **"Releases"** tab
4. Click **"Create a new release"**

### Method 4: If Releases Section is Missing
If you don't see a Releases section at all:
1. Go to your repository
2. Click on the **"Code"** tab
3. Look for **"Releases"** in the navigation bar (next to "Code", "Issues", etc.)
4. If it's not there, you can still create releases via the direct URL above

## Creating Your First Release

1. **Go to Releases Page**
   - Use one of the methods above
   - Or go to: `https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/releases/new`

2. **Fill in Release Information**
   - **Choose a tag**: Click "Choose a tag" dropdown
     - If no tags exist, type: `v1.0.2` and press Enter (this creates a new tag)
   - **Release title**: `Version 1.0.2`
   - **Description**: (Optional) Add release notes
     ```
     Version 1.0.2
     - Updated version number
     - Added WordPress auto-update from GitHub
     ```

3. **Publish Release**
   - Click **"Publish release"** button (green button at bottom)
   - For draft releases, click **"Save draft"** first, then **"Publish release"**

## Alternative: Using Git Tags (Command Line)

If you prefer using command line:

```bash
# Create and push a tag
git tag -a v1.0.2 -m "Version 1.0.2"
git push origin v1.0.2

# Then go to GitHub and create a release from this tag
```

## Quick Visual Guide

```
GitHub Repository Page
├── Code tab
├── Issues tab
├── Pull requests tab
├── Actions tab
├── Releases tab ← Look here!
│   └── "Create a new release" button
└── Right Sidebar
    ├── About
    └── Releases ← Or here!
        └── "Create a new release" link
```

## Still Can't Find It?

If you absolutely cannot find the Releases section:

1. **Check Repository Settings**
   - Go to Settings → General
   - Make sure the repository is not archived
   - Check that you have admin/write access

2. **Use GitHub CLI** (if installed)
   ```bash
   gh release create v1.0.2 --title "Version 1.0.2" --notes "Release notes"
   ```

3. **Use GitHub API** (advanced)
   - You can create releases via API
   - Or use the automated workflow we'll set up

## Next Steps

Once you create the release, WordPress will be able to detect it and show the update!

