# Database Setup Guide

## Quick Start

### 1. Configure Database Connection

Edit `.env` file with your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp_v2
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 2. Create Database

```bash
# Using psql
psql -U postgres
CREATE DATABASE erp_v2;
\q
```

Or using Laravel:

```bash
php artisan db:create erp_v2
```

### 3. Run Migrations

```bash
# Run all migrations
php artisan migrate

# Run migrations with seeders (includes demo data)
php artisan migrate:fresh --seed
```

### 4. Verify Installation

```bash
# Check migration status
php artisan migrate:status

# Access demo account
Email: admin@democompany.com
Password: password
```

---

## Migration Files Structure

All migrations are timestamped and named to execute in the correct dependency order:

```
migrations/
├── 0001_01_01_000000_create_users_table.php (Updated)
├── 0001_01_01_000001_create_cache_table.php
├── 0001_01_01_000002_create_jobs_table.php
├── 2026_04_14_174215_create_personal_access_tokens_table.php
├── 2026_04_19_010000_create_organizations_table.php
├── 2026_04_19_020000_create_branches_table.php
├── 2026_04_19_030000_create_roles_and_permissions_tables.php
├── 2026_04_19_040000_create_settings_table.php
├── 2026_04_19_050000_create_activity_logs_table.php
├── 2026_04_19_060000_create_attachments_table.php
├── 2026_04_19_070000_create_notifications_table.php
├── 2026_04_19_080000_create_payment_terms_table.php
├── 2026_04_19_090000_create_parties_tables.php
├── 2026_04_19_100000_create_accounts_table.php
├── 2026_04_19_110000_create_tax_rates_table.php
├── 2026_04_19_120000_create_invoices_tables.php
├── 2026_04_19_130000_create_journal_tables.php
├── 2026_04_19_140000_create_payments_tables.php
├── 2026_04_19_150000_create_product_categories_table.php
├── 2026_04_19_160000_create_units_table.php
├── 2026_04_19_170000_create_products_table.php
├── 2026_04_19_180000_create_warehouses_table.php
├── 2026_04_19_190000_create_stock_balances_table.php
├── 2026_04_19_200000_create_stock_movements_table.php
├── 2026_04_19_210000_create_stock_counts_tables.php
├── 2026_04_19_220000_create_employees_table.php
├── 2026_04_19_230000_create_attendance_records_table.php
├── 2026_04_19_240000_create_leave_requests_table.php
├── 2026_04_19_250000_create_salary_components_table.php
├── 2026_04_19_260000_create_payroll_tables.php
├── 2026_04_19_270000_create_projects_table.php
├── 2026_04_19_280000_create_project_members_table.php
├── 2026_04_19_290000_create_tasks_table.php
└── 2026_04_19_300000_create_time_entries_table.php
```

**Total Migrations:** 34 files  
**Total Tables:** 50+ tables

---

## Seeders

### Available Seeders

1. **RolesPermissionsSeeder**
   - Seeds 50+ permissions across all modules
   - Global permissions (not organization-scoped)

2. **DemoOrganizationSeeder**
   - Creates demo organization "Demo Company"
   - Creates main branch
   - Creates admin, manager, and accountant roles
   - Creates admin user (admin@democompany.com / password)
   - Seeds default settings
   - Seeds payment terms (Immediate, Net 7, 15, 30, 60 days)
   - Seeds tax rates (VAT 15%, Zero rated)
   - Seeds units of measurement

3. **ChartOfAccountsSeeder**
   - Creates hierarchical chart of accounts
   - 32 accounts across all 5 types
   - Levels 1-4 depth
   - Follows Saudi accounting standards

### Running Seeders

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=RolesPermissionsSeeder

# Fresh migration with seed
php artisan migrate:fresh --seed
```

---

## Database Features

### ✅ Multi-Tenancy
- Every record scoped to `organization_id`
- Global scope auto-filters queries
- Data isolation guaranteed

### ✅ Audit Trail
- `created_at` and `updated_at` on all tables
- Soft deletes for data preservation
- Activity logs for sensitive operations
- Actor tracking (who created/approved)

### ✅ Data Integrity
- Foreign key constraints
- Cascade delete where appropriate
- SET NULL for optional references
- UNIQUE constraints prevent duplicates

### ✅ Performance
- Strategic indexes on foreign keys
- Composite indexes for common queries
- JSONB for flexible metadata
- Optimized for PostgreSQL

### ✅ Financial Compliance
- DECIMAL precision for money
- Immutable journal entries (posted)
- Reversal transactions for corrections
- VAT calculation support

---

## Common Commands

### Migrations

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Rollback specific steps
php artisan migrate:rollback --step=3

# Reset all migrations (DANGER!)
php artisan migrate:reset

# Fresh start (DANGER!)
php artisan migrate:fresh

# Fresh with seed
php artisan migrate:fresh --seed

# Refresh (rollback all + re-run)
php artisan migrate:refresh
```

### Database Inspection

```bash
# Connect to database
php artisan db

# Show tables
php artisan db:show

# Show specific table
php artisan db:table users

# Export database
pg_dump -U postgres erp_v2 > backup.sql

# Import database
psql -U postgres erp_v2 < backup.sql
```

---

## Troubleshooting

### Issue: Migration fails with foreign key error

**Solution:** Ensure migrations run in order. Try:
```bash
php artisan migrate:fresh
```

### Issue: "SQLSTATE[42P07]: Duplicate table"

**Solution:** Table already exists. Either:
```bash
# Option 1: Reset and re-run
php artisan migrate:fresh

# Option 2: Drop table manually
php artisan tinker
Schema::drop('table_name');
exit
php artisan migrate
```

### Issue: Seeder fails

**Solution:** Ensure migrations completed successfully first:
```bash
php artisan migrate:status
# Then run seeders
php artisan db:seed
```

### Issue: "Class not found" for seeders

**Solution:** Regenerate autoload files:
```bash
composer dump-autoload
php artisan db:seed
```

---

## Database Backup & Restore

### Manual Backup

```bash
# Full database backup
pg_dump -U postgres -F c erp_v2 > erp_v2_backup_$(date +%Y%m%d).dump

# Schema only
pg_dump -U postgres --schema-only erp_v2 > schema.sql

# Data only
pg_dump -U postgres --data-only erp_v2 > data.sql
```

### Restore

```bash
# Restore from custom format
pg_restore -U postgres -d erp_v2 erp_v2_backup_20260419.dump

# Restore from SQL
psql -U postgres erp_v2 < backup.sql
```

### Automated Backup (Recommended)

Add to cron:
```bash
# Daily at 2 AM
0 2 * * * pg_dump -U postgres -F c erp_v2 > /backups/erp_$(date +\%Y\%m\%d).dump
```

---

## Performance Optimization

### Analyze Tables

```bash
# Via Laravel
php artisan db
ANALYZE;
VACUUM ANALYZE;
```

### Check Slow Queries

Enable slow query log in `postgresql.conf`:
```
log_min_duration_statement = 1000  # Log queries > 1 second
```

### Index Usage

```sql
-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan 
FROM pg_stat_user_indexes 
ORDER BY idx_scan;

-- Find missing indexes
SELECT schemaname, tablename, attname, n_distinct, correlation
FROM pg_stats
WHERE schemaname = 'public'
ORDER BY n_distinct DESC;
```

---

## Production Deployment Checklist

- [ ] Database created with proper user/password
- [ ] `.env` configured with production credentials
- [ ] Migrations executed successfully
- [ ] Seeders run (at minimum RolesPermissionsSeeder)
- [ ] Database backups configured
- [ ] Point-in-time recovery enabled
- [ ] Connection pooling configured (PgBouncer recommended)
- [ ] Monitoring setup (pg_stat_statements)
- [ ] SSL/TLS encryption enabled
- [ ] Regular VACUUM scheduled
- [ ] Query performance baseline established

---

## Schema Version

**Current Version:** 1.0.0  
**Last Updated:** April 19, 2026  
**PostgreSQL Required:** 13+  
**Laravel Version:** 11.x

---

## Support & Documentation

- Full schema documentation: `database/DATABASE_SCHEMA.md`
- Backend documentation: `BACKEND_DOCUMENTATION.md`
- For issues, contact the development team

---

**⚠️ Important Notes:**

1. **Always backup before migrations** in production
2. **Test migrations in staging** environment first
3. **Never use `migrate:fresh`** in production (data loss!)
4. **Review seeders** before running in production
5. **Monitor disk space** - PostgreSQL WAL can grow large

---

## Quick Reference

| Command | Description |
|---------|-------------|
| `php artisan migrate` | Run pending migrations |
| `php artisan migrate:fresh --seed` | Fresh start with demo data |
| `php artisan db:seed` | Run seeders only |
| `php artisan migrate:status` | Check migration status |
| `php artisan db` | Connect to database CLI |
| `pg_dump` | Backup database |
| `pg_restore` | Restore database |
