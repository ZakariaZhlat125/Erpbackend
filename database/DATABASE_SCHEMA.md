# ERP V2 - Database Schema Documentation

## Overview

This document describes the complete PostgreSQL database schema for the ERP V2 system. The schema follows Laravel best practices and implements a multi-tenant architecture with proper indexing, foreign key constraints, and data integrity rules.

## Database: PostgreSQL

**Version Required:** PostgreSQL 13 or higher  
**Character Set:** UTF-8  
**Timezone:** UTC (timestamps stored in UTC, converted per organization timezone)

---

## Migration Execution Order

Migrations must be executed in the following order to respect foreign key dependencies:

1. **Core Infrastructure** (010000-070000)
   - Organizations & Branches
   - Users & Authentication
   - Roles & Permissions
   - Settings, Activity Logs, Attachments
   - Notifications

2. **Parties Module** (080000-090000)
   - Payment Terms
   - Parties, Roles, Contacts, Addresses

3. **Accounting Module** (100000-140000)
   - Chart of Accounts
   - Tax Rates
   - Invoices & Lines
   - Journal Batches & Lines
   - Payments & Allocations

4. **Inventory Module** (150000-210000)
   - Product Categories
   - Units of Measurement
   - Products
   - Warehouses
   - Stock Balances
   - Stock Movements
   - Stock Counts

5. **HR Module** (220000-260000)
   - Employees
   - Attendance Records
   - Leave Requests
   - Salary Components
   - Payroll Runs & Lines

6. **Projects Module** (270000-300000)
   - Projects
   - Project Members
   - Tasks
   - Time Entries

---

## Module Breakdown

### 1. Core Module

#### organizations
Multi-tenant root entity. Every data record belongs to an organization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| name | VARCHAR(255) | NOT NULL | Display name |
| legal_name | VARCHAR(255) | NOT NULL | Official legal name |
| tax_number | VARCHAR(50) | UNIQUE, NOT NULL | Tax registration number |
| base_currency | VARCHAR(3) | DEFAULT 'SAR' | ISO currency code |
| timezone | VARCHAR(50) | DEFAULT 'Asia/Riyadh' | Timezone identifier |
| locale | VARCHAR(10) | DEFAULT 'ar' | Locale code |
| status | ENUM | DEFAULT 'active' | active/suspended/inactive |
| address | TEXT | NULL | Physical address |
| phone | VARCHAR(20) | NULL | Contact phone |
| email | VARCHAR(255) | NULL | Contact email |
| website | VARCHAR(255) | NULL | Website URL |
| logo_path | VARCHAR(255) | NULL | Logo file path |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `status`

---

#### branches
Physical locations/branches of an organization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| name | VARCHAR(255) | NOT NULL | Branch name |
| code | VARCHAR(50) | UNIQUE, NOT NULL | Branch code |
| address | TEXT | NULL | Branch address |
| phone | VARCHAR(20) | NULL | Branch phone |
| email | VARCHAR(255) | NULL | Branch email |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `organization_id, is_active`

**Foreign Keys:**
- `organization_id` CASCADE DELETE

---

#### users
System users with multi-tenant scoping.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| branch_id | BIGINT | FK, NULL | References branches(id) |
| name | VARCHAR(255) | NOT NULL | User full name |
| email | VARCHAR(255) | NOT NULL | Email address |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| phone | VARCHAR(20) | NULL | Phone number |
| avatar_path | VARCHAR(255) | NULL | Avatar image path |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| last_login_at | TIMESTAMP | NULL | Last login timestamp |
| last_login_ip | VARCHAR(45) | NULL | Last login IP address |
| remember_token | VARCHAR(100) | NULL | Remember me token |
| email_verified_at | TIMESTAMP | NULL | Email verification timestamp |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, email` (UNIQUE)
- `organization_id, is_active`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `branch_id` SET NULL ON DELETE

---

#### roles
Role definitions scoped per organization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| name | VARCHAR(255) | NOT NULL | Role identifier (slug) |
| label | VARCHAR(255) | NOT NULL | Human-readable label |
| description | TEXT | NULL | Role description |
| is_system | BOOLEAN | DEFAULT FALSE | System role (non-deletable) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `organization_id, name` (UNIQUE)

**Foreign Keys:**
- `organization_id` CASCADE DELETE

---

#### permissions
Global permission definitions (not organization-scoped).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| module | VARCHAR(50) | NOT NULL | Module name |
| action | VARCHAR(50) | NOT NULL | Action name |
| name | VARCHAR(255) | UNIQUE, NOT NULL | Permission identifier |
| label | VARCHAR(255) | NOT NULL | Human-readable label |
| description | TEXT | NULL | Permission description |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `module, action`

---

### 2. Parties Module

#### parties
Unified entity for customers, suppliers, agents, contractors.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| code | VARCHAR(50) | UNIQUE, NOT NULL | Party code |
| type | ENUM | DEFAULT 'company' | individual/company |
| display_name | VARCHAR(255) | NOT NULL | Display name |
| legal_name | VARCHAR(255) | NULL | Legal entity name |
| tax_number | VARCHAR(50) | NULL | Tax registration number |
| default_currency | VARCHAR(3) | DEFAULT 'SAR' | Default currency |
| notes | TEXT | NULL | Additional notes |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, display_name`
- `organization_id, is_active`

**Foreign Keys:**
- `organization_id` CASCADE DELETE

---

### 3. Accounting Module

#### accounts
Chart of accounts with hierarchical structure.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| code | VARCHAR(50) | NOT NULL | Account code |
| name | VARCHAR(255) | NOT NULL | Account name |
| type | ENUM | NOT NULL | asset/liability/equity/revenue/expense |
| parent_id | BIGINT | FK, NULL | References accounts(id) |
| level | TINYINT | DEFAULT 1 | Hierarchy level |
| allow_manual_entries | BOOLEAN | DEFAULT TRUE | Allow manual journal entries |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `organization_id, code` (UNIQUE)
- `organization_id, type, is_active`
- `parent_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `parent_id` SET NULL ON DELETE

---

#### invoices
Sales and purchase invoices.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| branch_id | BIGINT | FK, NULL | References branches(id) |
| number | VARCHAR(50) | NOT NULL | Invoice number |
| type | ENUM | NOT NULL | sale/purchase |
| party_id | BIGINT | FK, NOT NULL | References parties(id) |
| status | ENUM | DEFAULT 'draft' | draft/approved/partially_paid/paid/cancelled |
| issue_date | DATE | NOT NULL | Invoice issue date |
| due_date | DATE | NOT NULL | Payment due date |
| currency_code | VARCHAR(3) | DEFAULT 'SAR' | Currency code |
| subtotal | DECIMAL(15,2) | DEFAULT 0 | Subtotal before tax/discount |
| discount_total | DECIMAL(15,2) | DEFAULT 0 | Total discount amount |
| tax_total | DECIMAL(15,2) | DEFAULT 0 | Total tax amount |
| grand_total | DECIMAL(15,2) | DEFAULT 0 | Final total amount |
| payment_term_id | BIGINT | FK, NULL | References payment_terms(id) |
| notes | TEXT | NULL | Additional notes |
| created_by | BIGINT | FK, NOT NULL | References users(id) |
| approved_by | BIGINT | FK, NULL | References users(id) |
| approved_at | TIMESTAMP | NULL | Approval timestamp |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, number` (UNIQUE)
- `organization_id, type, status`
- `organization_id, type, issue_date`
- `organization_id, status, due_date`
- `party_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `branch_id` SET NULL ON DELETE
- `party_id` CASCADE DELETE
- `payment_term_id` SET NULL ON DELETE
- `created_by` CASCADE DELETE
- `approved_by` SET NULL ON DELETE

---

### 4. Inventory Module

#### products
Product and service catalog.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| sku | VARCHAR(100) | NOT NULL | Stock keeping unit |
| type | ENUM | DEFAULT 'product' | product/service |
| name | VARCHAR(255) | NOT NULL | Product name |
| description | TEXT | NULL | Product description |
| category_id | BIGINT | FK, NULL | References product_categories(id) |
| unit_id | BIGINT | FK, NULL | References units(id) |
| cost_price | DECIMAL(15,2) | DEFAULT 0 | Cost price |
| selling_price | DECIMAL(15,2) | DEFAULT 0 | Selling price |
| tax_rate_id | BIGINT | FK, NULL | References tax_rates(id) |
| track_inventory | BOOLEAN | DEFAULT TRUE | Track stock levels |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| image_path | VARCHAR(255) | NULL | Product image path |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, sku` (UNIQUE)
- `organization_id, type, is_active`
- `organization_id, name`
- `category_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `category_id` SET NULL ON DELETE
- `unit_id` SET NULL ON DELETE
- `tax_rate_id` SET NULL ON DELETE

---

#### stock_balances
Current stock levels (performance cache).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| warehouse_id | BIGINT | FK, NOT NULL | References warehouses(id) |
| product_id | BIGINT | FK, NOT NULL | References products(id) |
| quantity_on_hand | DECIMAL(12,3) | DEFAULT 0 | Available quantity |
| quantity_reserved | DECIMAL(12,3) | DEFAULT 0 | Reserved quantity |
| average_cost | DECIMAL(15,2) | DEFAULT 0 | Weighted average cost |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `warehouse_id, product_id` (UNIQUE)
- `organization_id, product_id`
- `warehouse_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `warehouse_id` CASCADE DELETE
- `product_id` CASCADE DELETE

---

### 5. HR Module

#### employees
Employee master data.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| branch_id | BIGINT | FK, NULL | References branches(id) |
| user_id | BIGINT | FK, NULL | References users(id) |
| employee_number | VARCHAR(50) | NOT NULL | Employee number |
| first_name | VARCHAR(255) | NOT NULL | First name |
| last_name | VARCHAR(255) | NOT NULL | Last name |
| full_name | VARCHAR(255) | NOT NULL | Full name |
| email | VARCHAR(255) | NULL | Email address |
| phone | VARCHAR(20) | NULL | Phone number |
| hire_date | DATE | NOT NULL | Hire date |
| job_title | VARCHAR(255) | NULL | Job title |
| department_name | VARCHAR(255) | NULL | Department name |
| manager_employee_id | BIGINT | FK, NULL | References employees(id) |
| status | ENUM | DEFAULT 'active' | active/inactive/terminated |
| base_salary | DECIMAL(15,2) | DEFAULT 0 | Base salary |
| currency_code | VARCHAR(3) | DEFAULT 'SAR' | Currency code |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, employee_number` (UNIQUE)
- `organization_id, status`
- `organization_id, full_name`
- `branch_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `branch_id` SET NULL ON DELETE
- `user_id` SET NULL ON DELETE
- `manager_employee_id` SET NULL ON DELETE

---

### 6. Projects Module

#### projects
Project management.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| organization_id | BIGINT | FK, NOT NULL | References organizations(id) |
| branch_id | BIGINT | FK, NULL | References branches(id) |
| code | VARCHAR(50) | NOT NULL | Project code |
| name | VARCHAR(255) | NOT NULL | Project name |
| description | TEXT | NULL | Project description |
| party_id | BIGINT | FK, NULL | References parties(id) |
| status | ENUM | DEFAULT 'planning' | planning/active/on_hold/completed/cancelled |
| start_date | DATE | NULL | Project start date |
| end_date | DATE | NULL | Project end date |
| budget_amount | DECIMAL(15,2) | DEFAULT 0 | Project budget |
| progress_percent | TINYINT | DEFAULT 0 | Progress percentage (0-100) |
| created_by | BIGINT | FK, NOT NULL | References users(id) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |
| deleted_at | TIMESTAMP | NULL | Soft delete timestamp |

**Indexes:**
- `organization_id, code` (UNIQUE)
- `organization_id, status`
- `party_id`

**Foreign Keys:**
- `organization_id` CASCADE DELETE
- `branch_id` SET NULL ON DELETE
- `party_id` SET NULL ON DELETE
- `created_by` CASCADE DELETE

---

## Best Practices Implemented

### 1. **Multi-Tenancy**
- Every tenant table includes `organization_id` with foreign key constraint
- Global scope automatically filters queries by organization
- Composite unique indexes include `organization_id`

### 2. **Indexing Strategy**
- Foreign keys are indexed
- Frequently queried columns (status, dates, names) are indexed
- Composite indexes for common query patterns
- Unique constraints prevent data duplication

### 3. **Data Integrity**
- Foreign key constraints with appropriate CASCADE/SET NULL actions
- ENUM types for fixed value sets
- NOT NULL constraints where applicable
- Soft deletes for audit trail preservation

### 4. **Performance Optimization**
- DECIMAL type for financial amounts (precision critical)
- Appropriate column sizes (VARCHAR lengths)
- JSONB for flexible metadata storage
- Strategic use of indexes without over-indexing

### 5. **Audit Trail**
- `created_at` and `updated_at` timestamps on all tables
- `activity_logs` table for sensitive operations
- Soft deletes (`deleted_at`) for recoverability
- Actor tracking (`created_by`, `approved_by`)

### 6. **Immutable Ledger**
- Journal entries cannot be deleted after posting
- Reversal transactions for corrections
- Status-based workflow enforcement

---

## Running Migrations

```bash
# Run all migrations
php artisan migrate

# Run migrations with seeding
php artisan migrate --seed

# Fresh migration (WARNING: destroys all data)
php artisan migrate:fresh --seed

# Rollback last batch
php artisan migrate:rollback

# Reset all migrations
php artisan migrate:reset
```

## Running Seeders

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=RolesPermissionsSeeder
php artisan db:seed --class=DemoOrganizationSeeder
php artisan db:seed --class=ChartOfAccountsSeeder
```

---

## Database Size Estimates

### Production Environment (1 Organization, 1 Year)

| Table | Estimated Rows | Avg Row Size | Total Size |
|-------|----------------|--------------|------------|
| invoices | 10,000 | 500 bytes | ~5 MB |
| invoice_lines | 50,000 | 300 bytes | ~15 MB |
| journal_lines | 100,000 | 250 bytes | ~25 MB |
| stock_movements | 50,000 | 300 bytes | ~15 MB |
| activity_logs | 200,000 | 500 bytes | ~100 MB |
| **Total Data** | | | **~500 MB** |
| **With Indexes** | | | **~750 MB** |

---

## Backup Strategy

1. **Daily automatic backups** at 2:00 AM UTC
2. **Retention:** 30 days daily, 12 months monthly
3. **Point-in-time recovery** enabled (WAL archiving)
4. **Backup verification** weekly
5. **Off-site replication** to secondary region

---

## Maintenance Tasks

### Weekly
- `VACUUM ANALYZE` for query planner statistics
- Index usage analysis
- Slow query log review

### Monthly
- Full table statistics update
- Partition maintenance (if implemented)
- Archive old audit logs (>1 year)

---

**Last Updated:** April 19, 2026  
**Schema Version:** 1.0.0  
**PostgreSQL Version:** 13+
