# Database Migration Checklist

## Pre-Migration Checklist

- [ ] PostgreSQL 13+ installed and running
- [ ] Database created: `erp_v2`
- [ ] Database user configured with proper permissions
- [ ] `.env` file updated with database credentials
- [ ] Backup of existing data (if applicable)
- [ ] All migration files present in `database/migrations/`

## Migration Execution Order

### ✅ Phase 1: Core Infrastructure (7 migrations)
```
2026_04_19_010000_create_organizations_table.php
2026_04_19_020000_create_branches_table.php
0001_01_01_000000_create_users_table.php (updated)
2026_04_19_030000_create_roles_and_permissions_tables.php
2026_04_19_040000_create_settings_table.php
2026_04_19_050000_create_activity_logs_table.php
2026_04_19_060000_create_attachments_table.php
2026_04_19_070000_create_notifications_table.php
```

### ✅ Phase 2: Parties Module (2 migrations)
```
2026_04_19_080000_create_payment_terms_table.php
2026_04_19_090000_create_parties_tables.php
```

### ✅ Phase 3: Accounting Module (5 migrations)
```
2026_04_19_100000_create_accounts_table.php
2026_04_19_110000_create_tax_rates_table.php
2026_04_19_120000_create_invoices_tables.php
2026_04_19_130000_create_journal_tables.php
2026_04_19_140000_create_payments_tables.php
```

### ✅ Phase 4: Inventory Module (7 migrations)
```
2026_04_19_150000_create_product_categories_table.php
2026_04_19_160000_create_units_table.php
2026_04_19_170000_create_products_table.php
2026_04_19_180000_create_warehouses_table.php
2026_04_19_190000_create_stock_balances_table.php
2026_04_19_200000_create_stock_movements_table.php
2026_04_19_210000_create_stock_counts_tables.php
```

### ✅ Phase 5: HR Module (5 migrations)
```
2026_04_19_220000_create_employees_table.php
2026_04_19_230000_create_attendance_records_table.php
2026_04_19_240000_create_leave_requests_table.php
2026_04_19_250000_create_salary_components_table.php
2026_04_19_260000_create_payroll_tables.php
```

### ✅ Phase 6: Projects Module (4 migrations)
```
2026_04_19_270000_create_projects_table.php
2026_04_19_280000_create_project_members_table.php
2026_04_19_290000_create_tasks_table.php
2026_04_19_300000_create_time_entries_table.php
```

## Execute Migrations

```bash
# Check migration status
php artisan migrate:status

# Run all migrations
php artisan migrate

# Expected output:
# Migration table created successfully.
# Migrating: 2026_04_19_010000_create_organizations_table
# Migrated:  2026_04_19_010000_create_organizations_table (XX ms)
# ... (all 34 migrations)
# Migration completed successfully!
```

## Post-Migration Verification

### 1. Check All Tables Created
```bash
php artisan db
\dt
# Should show 50+ tables
\q
```

### 2. Verify Foreign Keys
```sql
SELECT
    tc.table_name, 
    kcu.column_name, 
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name 
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
ORDER BY tc.table_name;
```

### 3. Verify Indexes
```sql
SELECT
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
```

### 4. Run Seeders
```bash
# Seed permissions
php artisan db:seed --class=RolesPermissionsSeeder

# Seed demo organization
php artisan db:seed --class=DemoOrganizationSeeder

# Seed chart of accounts
php artisan db:seed --class=ChartOfAccountsSeeder

# Or run all at once
php artisan db:seed
```

### 5. Verify Seeded Data
```bash
php artisan tinker

# Check organization
DB::table('organizations')->count();
# Should return: 1

# Check users
DB::table('users')->count();
# Should return: 1

# Check permissions
DB::table('permissions')->count();
# Should return: 50+

# Check accounts
DB::table('accounts')->count();
# Should return: 32

exit
```

## Rollback Plan (If Needed)

```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific number of batches
php artisan migrate:rollback --step=5

# Reset all migrations (DANGER!)
php artisan migrate:reset

# Fresh start (DANGER! - Drops all tables)
php artisan migrate:fresh
```

## Common Issues & Solutions

### Issue: "Base table or view already exists"
**Solution:**
```bash
php artisan migrate:fresh
```

### Issue: Foreign key constraint fails
**Solution:** Check migration order. Dependencies must be created first.
```bash
php artisan migrate:status
# Ensure all migrations are in correct order
```

### Issue: "SQLSTATE[42P01]: Undefined table"
**Solution:** Run migrations before seeders
```bash
php artisan migrate
php artisan db:seed
```

### Issue: Permission denied
**Solution:** Grant proper database permissions
```sql
GRANT ALL PRIVILEGES ON DATABASE erp_v2 TO your_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_user;
```

## Database Statistics After Migration

| Metric | Count |
|--------|-------|
| Total Tables | 50+ |
| Total Migrations | 34 |
| Total Foreign Keys | 80+ |
| Total Indexes | 100+ |
| Seeded Permissions | 50+ |
| Seeded Accounts | 32 |
| Seeded Roles | 3 |
| Seeded Users | 1 |

## Performance Baseline

After migrations complete, establish baseline:

```sql
-- Analyze all tables
ANALYZE;

-- Vacuum all tables
VACUUM ANALYZE;

-- Check database size
SELECT pg_size_pretty(pg_database_size('erp_v2'));

-- Check table sizes
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

## Production Deployment

### Before Deployment
- [ ] Test migrations in staging environment
- [ ] Backup existing production database
- [ ] Schedule maintenance window
- [ ] Notify users of downtime
- [ ] Prepare rollback plan

### During Deployment
- [ ] Put application in maintenance mode
- [ ] Backup current database
- [ ] Run migrations
- [ ] Verify all tables created
- [ ] Run essential seeders (permissions)
- [ ] Test database connectivity
- [ ] Smoke test critical features

### After Deployment
- [ ] Take application out of maintenance mode
- [ ] Monitor error logs
- [ ] Check database performance
- [ ] Verify user access
- [ ] Document any issues

## Estimated Migration Time

| Environment | Estimated Time |
|-------------|----------------|
| Local Development | 5-10 seconds |
| Staging | 10-20 seconds |
| Production (empty) | 10-20 seconds |
| Production (with data) | Varies by data volume |

## Support

For issues during migration:
1. Check `storage/logs/laravel.log`
2. Enable query logging in `.env`: `DB_LOG_QUERIES=true`
3. Review `database/DATABASE_SCHEMA.md`
4. Check PostgreSQL logs: `/var/log/postgresql/`

---

**Status:** ✅ All migrations created and ready for execution  
**Last Updated:** April 19, 2026  
**Version:** 1.0.0
