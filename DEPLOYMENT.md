# Deployment Guide

This repository is configured with GitHub Actions for automated validation and deployment to WordPress.

## Setup Instructions

### 1. GitHub Repository Setup

1. Create a private repository on GitHub
2. Push your code to the repository:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
   git branch -M main
   git add .
   git commit -m "Initial commit"
   git push -u origin main
   ```

### 2. Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions → New repository secret

#### For FTP Deployment:
- `WORDPRESS_FTP_SERVER` - Your WordPress FTP server (e.g., ftp.yoursite.com)
- `WORDPRESS_FTP_USERNAME` - FTP username
- `WORDPRESS_FTP_PASSWORD` - FTP password

#### For SSH Deployment (Alternative):
- `WORDPRESS_SSH_HOST` - Your WordPress server hostname or IP
- `WORDPRESS_SSH_USERNAME` - SSH username
- `WORDPRESS_SSH_KEY` - Your private SSH key
- `WORDPRESS_SSH_PORT` - SSH port (default: 22, optional)
- `WORDPRESS_SSH_PATH` - Path to WordPress root directory (e.g., /var/www/html)

**Note:** You only need to configure either FTP OR SSH secrets, not both.

### 3. Workflow Behavior

#### Automatic Validation
- **On Pull Requests**: Code is validated only (no deployment)
- **On Push to main/master**: Code is validated AND deployed to WordPress
- **Manual Trigger**: You can manually trigger deployment from GitHub Actions tab

#### Validation Steps
1. PHP syntax validation for all PHP files
2. Composer dependency installation and validation
3. Platform requirements check

#### Deployment Steps
1. Code is validated first
2. If validation passes, code is deployed to WordPress
3. Files are synced to `/wp-content/plugins/almokhlif-oud-sales-report/`

### 4. Deployment Methods

#### FTP Deployment (Default)
The workflow uses FTP to deploy files. Make sure:
- FTP credentials are correct
- FTP user has write permissions to the plugin directory
- Server path is correct (usually `/wp-content/plugins/almokhlif-oud-sales-report/`)

#### SSH Deployment (Alternative)
If you prefer SSH deployment:
1. Set up SSH key authentication on your server
2. Add the SSH secrets to GitHub
3. The workflow will automatically use SSH if `WORDPRESS_SSH_HOST` is set

### 5. Testing the Workflow

1. Make a change to your code
2. Commit and push to main branch:
   ```bash
   git add .
   git commit -m "Test deployment"
   git push origin main
   ```
3. Go to GitHub → Actions tab to see the workflow running
4. Check your WordPress site to verify the update

### 6. Troubleshooting

#### Validation Fails
- Check the Actions tab for error details
- Fix PHP syntax errors
- Ensure composer.json is valid

#### Deployment Fails
- Verify FTP/SSH credentials are correct
- Check server permissions
- Ensure the plugin directory exists on WordPress
- Review the Actions logs for specific error messages

#### Files Not Updating
- Clear WordPress cache if using caching plugins
- Check file permissions on the server
- Verify the deployment path is correct

## Security Notes

- Never commit secrets or credentials to the repository
- Use GitHub Secrets for all sensitive information
- Keep your repository private as configured
- Regularly rotate FTP/SSH credentials

