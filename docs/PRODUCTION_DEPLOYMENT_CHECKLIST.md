# Production Deployment Checklist

This checklist ensures a smooth and secure deployment of the Accounting Software to production.

## Pre-Deployment

### 1. Environment Setup
- [ ] Server meets minimum requirements (PHP 8.1+, MySQL 8.0+, Node.js 18+)
- [ ] Web server (Apache/Nginx) is installed and configured
- [ ] PHP extensions are installed: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`
- [ ] Composer is installed globally
- [ ] Node.js and npm are installed

### 2. Database Setup
- [ ] MySQL/MariaDB database is created
- [ ] Database user is created with appropriate permissions
- [ ] Database backup system is configured
- [ ] Backup scripts are tested and working

### 3. Security Configuration
- [ ] `.env` file is created with production values
- [ ] `APP_ENV=production` is set
- [ ] `APP_DEBUG=false` is set
- [ ] `APP_KEY` is generated (`php artisan key:generate`)
- [ ] Database credentials are secure
- [ ] Strong passwords are set for all accounts
- [ ] SSL certificate is configured (recommended)

### 4. File Permissions
- [ ] `storage/` directory is writable (775)
- [ ] `bootstrap/cache/` directory is writable (775)
- [ ] Web server user owns `storage/` and `bootstrap/cache/`
- [ ] `.env` file has restricted permissions (600)

## Deployment Steps

### 1. Code Deployment
- [ ] Latest code is pulled from repository
- [ ] Code is placed in web root directory
- [ ] `.env` file is configured with production settings
- [ ] `.gitignore` is verified (sensitive files excluded)

### 2. Dependencies Installation
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `npm ci` to install frontend dependencies
- [ ] Run `npm run build` to build production assets
- [ ] Verify all dependencies are installed correctly

### 3. Database Migration
- [ ] Database backup is created before migration
- [ ] Run `php artisan migrate --force`
- [ ] Verify all migrations completed successfully
- [ ] Run `php artisan db:seed` if initial data is needed

### 4. Application Optimization
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan event:cache`
- [ ] Clear old cache: `php artisan cache:clear`

### 5. Web Server Configuration
- [ ] Apache/Nginx virtual host is configured
- [ ] Document root points to `public/` directory
- [ ] URL rewriting is enabled
- [ ] Security headers are configured
- [ ] SSL is configured (if applicable)
- [ ] Web server is restarted

### 6. PHP Configuration
- [ ] PHP-FPM is configured and running
- [ ] PHP memory limit is adequate (256M+)
- [ ] PHP upload limits are configured
- [ ] PHP timezone is set correctly
- [ ] PHP error reporting is disabled in production

## Post-Deployment

### 1. Verification
- [ ] Application is accessible via web browser
- [ ] Login functionality works
- [ ] Database connections are working
- [ ] File uploads are working
- [ ] All routes are accessible
- [ ] API endpoints are responding

### 2. Security Checks
- [ ] HTTPS is enforced (if SSL is configured)
- [ ] CSRF protection is working
- [ ] Authentication is required for protected routes
- [ ] Role-based access control is working
- [ ] Input validation is working
- [ ] SQL injection protection is in place

### 3. Performance
- [ ] Page load times are acceptable
- [ ] Database queries are optimized
- [ ] Caching is working
- [ ] Asset compression is enabled
- [ ] CDN is configured (if applicable)

### 4. Monitoring
- [ ] Error logging is configured
- [ ] Application logs are being written
- [ ] Database backup schedule is set
- [ ] Monitoring tools are configured (optional)
- [ ] Uptime monitoring is set up (optional)

### 5. Documentation
- [ ] Deployment documentation is updated
- [ ] Server access credentials are documented (securely)
- [ ] Database credentials are documented (securely)
- [ ] Backup procedures are documented
- [ ] Rollback procedures are documented

## Automated Deployment

For automated deployment, use the provided scripts:

### Linux/Unix:
```bash
chmod +x scripts/deploy.sh
sudo ./scripts/deploy.sh
```

### Windows:
```powershell
.\scripts\deploy.ps1
```

## Rollback Procedure

If deployment fails:

1. **Restore Database:**
   ```bash
   # Restore from latest backup
   mysql -u username -p database_name < backup_file.sql
   ```

2. **Restore Code:**
   ```bash
   # Revert to previous commit
   git reset --hard HEAD~1
   ```

3. **Clear Cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Restart Services:**
   ```bash
   sudo systemctl restart nginx
   sudo systemctl restart php8.2-fpm
   ```

## Maintenance

### Regular Tasks
- [ ] Monitor application logs daily
- [ ] Review error logs weekly
- [ ] Test database backups monthly
- [ ] Update dependencies quarterly
- [ ] Review security patches monthly
- [ ] Monitor disk space usage
- [ ] Review performance metrics

### Backup Schedule
- **Database:** Daily automated backups
- **Code:** Version controlled (Git)
- **Files:** Weekly backups of `storage/` directory
- **Configuration:** Documented in secure location

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check file permissions
   - Review error logs: `storage/logs/laravel.log`
   - Verify `.env` configuration

2. **Database Connection Error**
   - Verify database credentials in `.env`
   - Check database server is running
   - Verify network connectivity

3. **Asset Loading Issues**
   - Run `npm run build` again
   - Clear browser cache
   - Check `public/build/` directory exists

4. **Permission Errors**
   - Set correct permissions: `chmod -R 775 storage bootstrap/cache`
   - Set correct ownership: `chown -R www-data:www-data storage bootstrap/cache`

## Support

For issues or questions:
- Review documentation in `docs/` directory
- Check application logs in `storage/logs/`
- Review web server error logs
- Contact system administrator
