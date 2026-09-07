# DILP Reporting System — Production Deployment Checklist

## Server requirements
- PHP 8.3+
- MySQL 8+
- Composer 2
- Node.js 20+
- PHP extensions required by Laravel plus `zip` for XLSX legacy import
- HTTPS certificate for production

## Production environment
Use production values similar to:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-dilp-domain.example
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
LEGACY_IMPORT_MAX_MB=25
LEGACY_IMPORT_MAX_ROWS=50000
```

Do not copy a development `.env` into production. Generate and protect a production `APP_KEY`.

## Deployment commands
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure the web-server user can write to `storage/` and `bootstrap/cache/`.

## Before go-live
1. Back up the database.
2. Confirm `/up` reports healthy.
3. Confirm HTTPS and secure cookies.
4. Confirm Super Admin access and at least one backup Super Admin account.
5. Test project creation, workflow, processing, monitoring, reports, Excel export, private document download, and legacy import dry-run.
6. Review **Administration → Data Quality**.
7. Review **Administration → Audit Logs**.
8. Import legacy data in controlled batches and reconcile official report totals.

## Backup policy
Back up both:
- MySQL database
- `storage/app/private` / local private storage containing documents and source migration files

Keep backups encrypted and outside the application server.

## Security controls included
- Role/permission authorization
- Active-account enforcement
- Private document/import file storage
- Security response headers
- Append-only application audit log screen
- Create-only spreadsheet migration with duplicate protection
- Laravel `/up` health endpoint
