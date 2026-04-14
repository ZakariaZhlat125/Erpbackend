# ERP V2 — Backend Documentation

> **Generated:** April 14, 2026
> **Source:** Tamed.sa ERP analysis + ERP V2 Blueprint Pack
> **Stack:** Laravel (Modular Monolith) + PostgreSQL + Redis

---

## 1. Overview

ERP V2 backend is a **Laravel Modular Monolith** — one Laravel application, one PostgreSQL database, organized into clearly bounded domain modules. Each module owns its models, services, routes, and business rules.

### Design Principles
- **Thin controllers** — delegate to Application Services / Actions
- **Business logic in Application layer** — never in routes, middleware, or Blade/Inertia props
- **Immutable ledger** — posted journal entries are never deleted; use reversal documents
- **Multi-tenant by default** — every query scoped to `organization_id` via Global Scope
- **Auditability from day one** — every financially sensitive operation writes an `ActivityLog`
- **API-first for external integrations** — Admin UI uses Inertia; external clients use versioned REST API

---

## 2. Technology Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel (latest stable, PHP 8.3+) |
| **Database** | PostgreSQL |
| **Cache / Queue** | Redis |
| **Queue driver** | Laravel Horizon (Redis) |
| **Scheduler** | Laravel Scheduler |
| **Admin UI bridge** | Inertia.js |
| **Auth (Admin UI)** | Laravel Sanctum (session) |
| **Auth (API)** | Laravel Sanctum (personal access tokens) |
| **Authorization** | Laravel Policies + Gates |
| **ORM** | Eloquent |
| **Validation** | Form Request classes |
| **PDF generation** | DomPDF or Puppeteer (Node sidecar) |
| **Excel import/export** | Maatwebsite Excel |
| **File storage** | S3-compatible (MinIO local / AWS S3 prod) |
| **Email** | Resend or AWS SES via Laravel Mail |
| **SMS** | Yamamah SMS API |
| **WhatsApp** | Meta Graph API |
| **Real-time** | Pusher or self-hosted Soketi |
| **API docs** | Scribe or OpenAPI/Swagger |
| **Error tracking** | Sentry |
| **Dev monitoring** | Laravel Telescope |
| **Prod monitoring** | Datadog / Prometheus |

---

## 3. Architecture

### 3.1 Modular Monolith

```
One Laravel application
  ├── One PostgreSQL database
  ├── Multiple bounded domain modules  (app/Modules/)
  ├── Shared infrastructure            (app/Support/)
  └── Shared queue / scheduler / events
```

Modules communicate via:
- **Laravel Events** — for cross-module side effects (e.g., `InvoiceApproved` → Audit, Notifications, Inventory)
- **Direct Service calls** — only when tightly coupled by business logic and explicitly intended

### 3.2 Request Lifecycle

```
HTTP Request
  → Route (routes/modules/{module}.php)
  → Form Request  (input validation + basic authorization)
  → Policy / Gate (authorization: can this user do this action on this resource?)
  → Controller    (thin: receives, delegates, returns response)
  → Action / Service (business logic + DB transaction)
  → Eloquent      (persistence)
  → Domain Events (dispatched after commit)
  → ActivityLog   (written)
  → Inertia redirect OR JSON response
```

### 3.3 Module Internal Layers

```text
app/Modules/{ModuleName}/
├── Domain/
│   ├── Models/          # Eloquent models
│   ├── Enums/           # PHP 8.1 backed enums (InvoiceStatus, MovementType…)
│   └── Rules/           # Custom Laravel validation rules
├── Application/
│   ├── Actions/         # Single-purpose: CreateInvoiceAction, ApprovePayrollAction…
│   ├── Queries/         # Read-only queries for lists and reports
│   ├── DTOs/            # Data Transfer Objects
│   └── Services/        # Multi-step orchestration (PayrollService, StockService)
├── Infrastructure/
│   ├── Persistence/     # Custom query builders, Eloquent scopes
│   └── Integrations/    # Third-party wrappers (ZatcaService, SmsService…)
└── UI/
    ├── Controllers/     # HTTP controllers (thin)
    ├── Requests/        # Form Request classes (one per action)
    ├── Resources/       # API Resources / Inertia props transformers
    └── Policies/        # Laravel Policies (one per model)
```

---

## 4. Project Folder Structure

```text
erp-v2/
├── app/
│   ├── Modules/
│   │   ├── Core/            # Users, Roles, Permissions, Settings, Auth
│   │   ├── Organization/    # Organization, Branches
│   │   ├── Parties/         # Party, PartyRole, Contact, Address, PaymentTerm
│   │   ├── Accounting/      # Invoice, Payment, Account, JournalBatch, TaxRate
│   │   ├── Inventory/       # Product, Warehouse, StockBalance, StockMovement
│   │   ├── HR/              # Employee, Attendance, Leave, Payroll
│   │   ├── Projects/        # Project, Task, TimeEntry, ProjectMember
│   │   ├── Documents/       # Attachment (polymorphic)
│   │   ├── Audit/           # ActivityLog
│   │   ├── Notifications/   # Notification, NotificationPreference
│   │   └── Reports/         # Read-only report query classes
│   ├── Support/
│   │   ├── Scopes/          # OrganizationScope (global multi-tenant filter)
│   │   ├── Tax/             # TaxCalculator utility
│   │   ├── Pagination/      # PaginationHelper
│   │   └── Traits/          # HasOrganization, HasAudit, HasAttachments
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── ModuleServiceProvider.php   # Registers all module routes + bindings
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── filesystems.php
│   └── erp.php              # ERP-specific: VAT rate, feature flags, limits
│
├── database/
│   ├── migrations/          # Ordered migrations (see Section 7)
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── RolesPermissionsSeeder.php
│   │   ├── DemoOrganizationSeeder.php
│   │   └── DemoDataSeeder.php
│   └── factories/
│
├── resources/
│   ├── js/                  # Frontend (see FRONTEND_DOCUMENTATION.md)
│   ├── css/
│   └── views/
│       └── emails/          # Blade email templates
│
├── routes/
│   ├── web.php              # Inertia: auth pages only
│   ├── api.php              # External API: /api/v1/*
│   └── modules/             # Per-module Inertia + web routes
│       ├── accounting.php
│       ├── inventory.php
│       ├── hr.php
│       └── projects.php
│
├── tests/
│   ├── Unit/                # Unit tests for Actions, Services, TaxCalculator
│   ├── Feature/             # Feature tests: full HTTP request cycle per module
│   └── Integration/         # DB-level integration tests
│
└── docker/
    ├── Dockerfile
    ├── docker-compose.yml
    └── nginx.conf
```

---

## 5. Domain Modules

### 5.1 Core (Users, Roles, Permissions, Settings)

**Responsibility:** Authentication, authorization, system configuration.

**Key Models:** `User`, `Role`, `Permission`, `Setting`

**Key Actions:**
- `InviteUserAction` — creates user with hashed password, assigns role, sends invitation email
- `AssignPermissionsAction` — attaches permissions array to a role
- `UpdateSettingAction` — stores org-level setting, flushes Redis cache for that key

**Auth Flow (Admin UI / Inertia):**
```
POST /login
  → validate credentials
  → Sanctum creates session cookie
  → auth.user injected to all Inertia pages via HandleInertiaRequests middleware
```

**Auth Flow (External API):**
```
POST /api/v1/auth/login
  → validate credentials
  → Sanctum issues personal access token with abilities
  → Client sends: Authorization: Bearer {token}
```

**Permission System:**
- Permissions stored in DB table (`module`, `action`, `name`)
- Roles assigned to users via `model_has_roles` pivot
- Permissions assigned to roles via `role_has_permissions` pivot
- Laravel `Gate::define()` called from `ModuleServiceProvider` per permission
- Controllers and Policies check via `$this->authorize()` or `Gate::allows()`

---

### 5.2 Organization & Multi-Tenancy

**Responsibility:** Org-level scoping of all data. Every record belongs to an organization.

**Key Models:** `Organization`, `Branch`

**Global Scope (applied to all tenant models):**
```php
// app/Support/Scopes/OrganizationScope.php
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($user = auth()->user()) {
            $builder->where($model->getTable() . '.organization_id', $user->organization_id);
        }
    }
}
```

**Trait applied to all tenant models:**
```php
trait HasOrganization
{
    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope());
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }
}
```

---

### 5.3 Parties

**Responsibility:** Unified entity for all external parties (customers, suppliers, agents).

**Key Models:** `Party`, `PartyRole`, `PartyContact`, `PartyAddress`, `PaymentTerm`

**Design Decision:** Single `parties` table instead of separate `customers` / `suppliers`. A party holds multiple roles simultaneously (e.g., both customer and supplier).

**Key Service:**
```php
// app/Modules/Parties/Application/Services/PartyService.php
class PartyService
{
    public function create(CreatePartyDTO $dto): Party
    {
        return DB::transaction(function () use ($dto) {
            $party = Party::create([...]);
            foreach ($dto->roles as $role) {
                $party->roles()->create(['role' => $role]);
            }
            foreach ($dto->contacts as $contact) {
                $party->contacts()->create([...$contact]);
            }
            ActivityLog::record(auth()->user(), 'party.created', $party);
            return $party;
        });
    }

    public function statement(Party $party, Carbon $from, Carbon $to): array
    {
        // Returns invoices + payments for this party in date range
    }
}
```

---

### 5.4 Accounting

**Responsibility:** Chart of accounts, invoices, payments, journal entries, tax calculation.

**Key Models:** `Account`, `JournalBatch`, `JournalLine`, `Invoice`, `InvoiceLine`, `Payment`, `PaymentAllocation`, `TaxRate`

**Invoice Status (PHP Enum):**
```php
enum InvoiceStatus: string
{
    case Draft        = 'draft';
    case Approved     = 'approved';
    case PartiallyPaid = 'partially_paid';
    case Paid         = 'paid';
    case Cancelled    = 'cancelled';
}
```

**Invoice Approval Action:**
```php
class ApproveInvoiceAction
{
    public function execute(Invoice $invoice, User $actor): Invoice
    {
        throw_unless($invoice->status === InvoiceStatus::Draft,
            new BusinessRuleException('Only draft invoices can be approved.'));

        return DB::transaction(function () use ($invoice, $actor) {
            $invoice->update([
                'status'      => InvoiceStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            // Create journal entries
            $batch = JournalBatch::create([
                'source_type'  => Invoice::class,
                'source_id'    => $invoice->id,
                'posting_date' => $invoice->issue_date,
                'status'       => 'posted',
                'description'  => "Invoice #{$invoice->number}",
            ]);

            // DR: Receivable account, CR: Revenue account (per line)
            $this->postJournalLines($batch, $invoice);

            ActivityLog::record($actor, 'invoice.approved', $invoice);
            event(new InvoiceApproved($invoice));

            return $invoice->fresh();
        });
    }
}
```

**Tax Calculator:**
```php
// app/Support/Tax/TaxCalculator.php
class TaxCalculator
{
    public function calculateLine(
        float $unitPrice,
        float $quantity,
        float $discountAmount,
        float $taxRate
    ): array {
        $subtotal   = round($unitPrice * $quantity, 2);
        $afterDisc  = round($subtotal - $discountAmount, 2);
        $taxAmount  = round($afterDisc * ($taxRate / 100), 2);
        $lineTotal  = round($afterDisc + $taxAmount, 2);

        return compact('subtotal', 'afterDisc', 'taxAmount', 'lineTotal');
    }
}
```

**Immutable Ledger Rule:**
- `journal_lines` are NEVER deleted after posting
- Cancelling an invoice creates a **reversing** `JournalBatch` (all debits/credits flipped)
- No raw `DELETE` on `journal_batches` or `journal_lines` outside of draft status

---

### 5.5 Inventory

**Responsibility:** Product catalog, warehouses, stock balances, movements, physical counts.

**Key Models:** `Product`, `ProductCategory`, `Unit`, `Warehouse`, `StockBalance`, `StockMovement`, `StockCount`, `StockCountLine`

**Stock Rules:**
- `stock_balances` = performance cache (current quantity per warehouse+product)
- `stock_movements` = source of truth (append-only history)
- Negative stock blocked by default; configurable per warehouse

**Create Stock Movement Action:**
```php
class CreateStockMovementAction
{
    public function execute(CreateMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto) {
            $balance = StockBalance::lockForUpdate()
                ->where('warehouse_id', $dto->warehouseId)
                ->where('product_id', $dto->productId)
                ->firstOrCreate([...]);

            if ($dto->type === 'out' && $balance->quantity_on_hand < $dto->quantity) {
                throw new InsufficientStockException();
            }

            $movement = StockMovement::create([...]);

            $delta = in_array($dto->type, ['in', 'adjustment_in']) ? $dto->quantity : -$dto->quantity;
            $balance->increment('quantity_on_hand', $delta);

            event(new StockMoved($movement));
            return $movement;
        });
    }
}
```

**Approve Stock Count Action:**
```php
class ApproveStockCountAction
{
    public function execute(StockCount $count, User $actor): void
    {
        DB::transaction(function () use ($count, $actor) {
            foreach ($count->lines as $line) {
                if ($line->variance_qty !== 0.0) {
                    $type = $line->variance_qty > 0 ? 'adjustment_in' : 'adjustment_out';
                    $this->movementAction->execute(new CreateMovementDTO(
                        warehouseId: $count->warehouse_id,
                        productId:   $line->product_id,
                        type:        $type,
                        quantity:    abs($line->variance_qty),
                        referenceType: StockCount::class,
                        referenceId:   $count->id,
                    ));
                }
            }
            $count->update(['status' => 'approved', 'approved_by' => $actor->id]);
            ActivityLog::record($actor, 'stock_count.approved', $count);
        });
    }
}
```

---

### 5.6 HR (Human Resources)

**Responsibility:** Employees, attendance, leaves, payroll.

**Key Models:** `Employee`, `AttendanceRecord`, `LeaveRequest`, `SalaryComponent`, `PayrollRun`, `PayrollLine`

**Payroll Calculation Formula:**
```
net_amount =
    base_salary
  + SUM(earning components)
  + SUM(approved bonuses this period)
  - SUM(deduction components)
  - SUM(advances to deduct this period)
  - leave_deduction (if leave type is unpaid)
```

**Generate Payroll Run Action:**
```php
class GeneratePayrollRunAction
{
    public function execute(GeneratePayrollDTO $dto): PayrollRun
    {
        return DB::transaction(function () use ($dto) {
            $run = PayrollRun::create([
                'period_start' => $dto->periodStart,
                'period_end'   => $dto->periodEnd,
                'status'       => 'draft',
                'generated_by' => auth()->id(),
            ]);

            $employees = Employee::where('status', 'active')->get();
            foreach ($employees as $employee) {
                $breakdown = $this->calculator->compute($employee, $dto);
                PayrollLine::create([
                    'payroll_run_id'   => $run->id,
                    'employee_id'      => $employee->id,
                    'gross_amount'     => $breakdown['gross'],
                    'deduction_amount' => $breakdown['deductions'],
                    'net_amount'       => $breakdown['net'],
                    'breakdown_json'   => $breakdown,
                ]);
            }
            return $run;
        });
    }
}
```

**Approve Payroll — posts to Accounting:**
```php
class ApprovePayrollRunAction
{
    public function execute(PayrollRun $run, User $actor): void
    {
        DB::transaction(function () use ($run, $actor) {
            // Create JournalBatch in Accounting module
            $batch = JournalBatch::create([
                'source_type'  => PayrollRun::class,
                'source_id'    => $run->id,
                'posting_date' => $run->period_end,
                'description'  => "Payroll {$run->period_start} – {$run->period_end}",
                'status'       => 'posted',
            ]);

            foreach ($run->lines as $line) {
                // DR: Salary Expense, CR: Salary Payable
                JournalLine::create([...]);
            }

            $run->update(['status' => 'posted', 'approved_by' => $actor->id, 'approved_at' => now()]);
            ActivityLog::record($actor, 'payroll.approved', $run);
            event(new PayrollApproved($run));
        });
    }
}
```

---

### 5.7 Projects

**Responsibility:** Projects, members, tasks, time entries.

**Key Models:** `Project`, `ProjectMember`, `Task`, `TimeEntry`

**Task Status (PHP Enum):**
```php
enum TaskStatus: string
{
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case Blocked    = 'blocked';
    case Done       = 'done';
    case Cancelled  = 'cancelled';
}
```

**Rules:**
- Time entries must belong to a valid project within same organization
- Task must have a clear responsible user before moving to `in_progress`
- Completed task actual_minutes updated from sum of time entries

---

### 5.8 Audit & Activity Logging

**Key Model:** `ActivityLog`

```php
// app/Modules/Audit/Domain/Models/ActivityLog.php
class ActivityLog extends Model
{
    public static function record(
        User   $actor,
        string $action,
        Model  $subject,
        array  $oldValues = [],
        array  $newValues = [],
    ): void {
        static::create([
            'organization_id' => $actor->organization_id,
            'actor_id'        => $actor->id,
            'action'          => $action,
            'subject_type'    => get_class($subject),
            'subject_id'      => $subject->getKey(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
        ]);
    }
}
```

**Actions that trigger audit:**

| Action | Log Entry |
|--------|-----------|
| Invoice approved | `invoice.approved` |
| Invoice cancelled | `invoice.cancelled` |
| Payment recorded | `payment.recorded` |
| Journal posted | `journal.posted` |
| Stock count approved | `stock_count.approved` |
| Payroll approved | `payroll.approved` |
| Employee created | `employee.created` |
| Employee deactivated | `employee.deactivated` |
| Role permissions changed | `role.permissions_updated` |
| Settings updated | `settings.updated` |

---

## 6. Database Schema

### 6.1 Core Tables

```sql
organizations   (id, name, legal_name, tax_number, base_currency, timezone, locale, status)
branches        (id, organization_id, name, code, address, phone, is_active)
users           (id, organization_id, branch_id, name, email, password, phone,
                 avatar_path, is_active, last_login_at, last_login_ip, deleted_at)
roles           (id, organization_id, name, label, is_system)
permissions     (id, module, action, name, label)
model_has_roles (model_id, model_type, role_id)
role_has_permissions (role_id, permission_id)
settings        (id, organization_id, group, key, value_json, type)
activity_logs   (id, organization_id, actor_id, action, subject_type, subject_id,
                 old_values_json, new_values_json, meta_json, ip_address, user_agent, created_at)
attachments     (id, organization_id, attachable_type, attachable_id,
                 disk, path, file_name, mime_type, size, uploaded_by, created_at)
```

### 6.2 Parties Tables

```sql
parties         (id, organization_id, code, type, display_name, legal_name,
                 tax_number, default_currency, notes, is_active, deleted_at)
party_roles     (id, party_id, role ENUM(customer/supplier/agent/contractor))
party_contacts  (id, party_id, name, email, phone, position, is_primary)
party_addresses (id, party_id, label, country, city, line_1, line_2, postal_code, is_primary)
payment_terms   (id, organization_id, name, due_days, notes)
```

### 6.3 Accounting Tables

```sql
accounts        (id, organization_id, code, name,
                 type ENUM(asset/liability/equity/revenue/expense),
                 parent_id, level, allow_manual_entries, is_active)

tax_rates       (id, organization_id, name, code, rate DECIMAL(5,2), is_default, is_active)

invoices        (id, organization_id, branch_id, number, type ENUM(sale/purchase),
                 party_id, status ENUM(draft/approved/partially_paid/paid/cancelled),
                 issue_date, due_date, currency_code,
                 subtotal, discount_total, tax_total, grand_total DECIMAL(15,2),
                 payment_term_id, notes, created_by, approved_by, approved_at)

invoice_lines   (id, invoice_id, product_id, description, quantity DECIMAL(12,3),
                 unit_price, discount_amount, tax_rate_id, tax_amount, line_total,
                 sort_order SMALLINT)

journal_batches (id, organization_id, number, source_type, source_id,
                 posting_date, status ENUM(draft/posted/reversed),
                 description, created_by, posted_by, posted_at)

journal_lines   (id, journal_batch_id, account_id, debit, credit DECIMAL(15,2),
                 currency_code, exchange_rate, description,
                 party_id, project_id, employee_id)

payments        (id, organization_id, party_id, invoice_id, number,
                 direction ENUM(inbound/outbound),
                 method ENUM(cash/bank_transfer/cheque/card),
                 amount, currency_code, paid_at, reference, notes, created_by)

payment_allocations (id, payment_id, invoice_id, allocated_amount)
```

### 6.4 Inventory Tables

```sql
product_categories (id, organization_id, name, parent_id, is_active)
units              (id, organization_id, name, symbol, is_base)
products           (id, organization_id, sku, type ENUM(product/service), name,
                    description, category_id, unit_id, cost_price, selling_price,
                    tax_rate_id, track_inventory, is_active)

warehouses         (id, organization_id, branch_id, name, code, address,
                    manager_user_id, is_active)

stock_balances     (id, organization_id, warehouse_id, product_id,
                    quantity_on_hand DECIMAL(12,3), quantity_reserved, average_cost,
                    updated_at,
                    UNIQUE(warehouse_id, product_id))

stock_movements    (id, organization_id, warehouse_id, product_id,
                    movement_type ENUM(in/out/transfer/adjustment_in/adjustment_out),
                    quantity DECIMAL(12,3), unit_cost, reference_type, reference_id,
                    notes, moved_at, created_by)

stock_counts       (id, organization_id, warehouse_id,
                    status ENUM(draft/in_progress/approved),
                    counted_at, created_by, approved_by)

stock_count_lines  (id, stock_count_id, product_id,
                    system_qty, counted_qty, variance_qty DECIMAL(12,3))
```

### 6.5 HR Tables

```sql
employees          (id, organization_id, branch_id, user_id,
                    employee_number, first_name, last_name, full_name,
                    email, phone, hire_date, job_title, department_name,
                    manager_employee_id, status ENUM(active/inactive/terminated),
                    base_salary, currency_code, deleted_at)

attendance_records (id, organization_id, employee_id, date DATE,
                    check_in_at, check_out_at, worked_minutes,
                    status ENUM(present/absent/late/on_leave),
                    source ENUM(manual/device/import), notes,
                    UNIQUE(employee_id, date))

leave_requests     (id, organization_id, employee_id,
                    leave_type ENUM(annual/sick/unpaid/other),
                    start_date, end_date, days_count, reason,
                    status ENUM(pending/approved/rejected/cancelled),
                    approved_by, approved_at)

salary_components  (id, organization_id, name, code,
                    type ENUM(earning/deduction),
                    calculation_mode ENUM(fixed/formula/manual),
                    is_taxable, is_active)

payroll_runs       (id, organization_id, period_start, period_end,
                    status ENUM(draft/reviewed/approved/posted),
                    generated_by, approved_by, approved_at)

payroll_lines      (id, payroll_run_id, employee_id,
                    gross_amount, deduction_amount, net_amount,
                    breakdown_json JSONB)
```

### 6.6 Projects Tables

```sql
projects        (id, organization_id, branch_id, code, name, description,
                 party_id, status ENUM(planning/active/on_hold/completed/cancelled),
                 start_date, end_date, budget_amount, progress_percent, created_by)

project_members (id, project_id, user_id,
                 role ENUM(manager/member/viewer), hourly_rate,
                 UNIQUE(project_id, user_id))

tasks           (id, organization_id, project_id, parent_id, title, description,
                 status ENUM(todo/in_progress/blocked/done/cancelled),
                 priority ENUM(low/medium/high/urgent),
                 assigned_user_id, due_date, estimated_minutes, actual_minutes,
                 created_by)

time_entries    (id, organization_id, project_id, task_id, user_id,
                 entry_date, minutes, notes, is_billable)
```

### 6.7 Required Indexes

```sql
CREATE INDEX ON users (organization_id, email);
CREATE INDEX ON parties (organization_id, display_name);
CREATE INDEX ON invoices (organization_id, type, issue_date);
CREATE INDEX ON invoices (organization_id, status, due_date);
CREATE INDEX ON journal_lines (account_id);
CREATE INDEX ON payments (organization_id, paid_at);
CREATE UNIQUE INDEX ON stock_balances (warehouse_id, product_id);
CREATE INDEX ON stock_movements (organization_id, moved_at);
CREATE UNIQUE INDEX ON attendance_records (employee_id, date);
CREATE INDEX ON tasks (project_id, status, assigned_user_id);
CREATE INDEX ON activity_logs (organization_id, subject_type, subject_id);
```

### 6.8 Migration Order

```
1.  organizations, branches
2.  users, roles, permissions, model_has_roles, role_has_permissions
3.  settings, activity_logs, attachments, notifications
4.  payment_terms, parties, party_roles, party_contacts, party_addresses
5.  accounts, tax_rates
6.  invoices, invoice_lines, journal_batches, journal_lines, payments, payment_allocations
7.  product_categories, units, products, warehouses, stock_balances, stock_movements,
    stock_counts, stock_count_lines
8.  employees, salary_components, attendance_records, leave_requests,
    payroll_runs, payroll_lines
9.  projects, project_members, tasks, time_entries
```

---

## 7. API Specification

### 7.1 Conventions

- **Base URL:** `/api/v1/`
- **Format:** JSON only (`Accept: application/json`)
- **Auth:** `Authorization: Bearer {token}` (Sanctum personal access token)
- **Pagination:** default 15 per page; all list endpoints paginated
- **Versioning:** breaking changes increment to `/api/v2/`

### 7.2 Standard Response Envelopes

**Single resource:**
```json
{ "data": { "id": "uuid", "..." } }
```

**Paginated list:**
```json
{
  "data": [],
  "meta": { "current_page": 1, "per_page": 15, "total": 120, "last_page": 8 },
  "links": { "next": "...", "prev": null }
}
```

**Validation error (422):**
```json
{
  "message": "Validation failed",
  "errors": { "party_id": ["The party id field is required."] }
}
```

**Business rule error (409):**
```json
{
  "message": "Invoice cannot be approved: it is already cancelled.",
  "errors": {}
}
```

**HTTP status codes used:**
| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Forbidden (no permission) |
| 404 | Not found |
| 409 | Business rule violation / conflict |
| 422 | Validation failed |
| 500 | Server error |

**Common query params for all list endpoints:**
`?page=&per_page=&sort=&direction=&search=`

---

### 7.3 Auth API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/v1/auth/login` | Login, returns Sanctum token |
| POST | `/api/v1/auth/logout` | Revoke token |
| GET | `/api/v1/auth/me` | Get current user + roles + permissions |

**Login payload:**
```json
{ "email": "admin@example.com", "password": "secret" }
```
**Login response:**
```json
{ "token": "...", "user": { "id", "name", "email", "roles": [], "permissions": [] } }
```

---

### 7.4 Users & Roles API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/users` | List users |
| POST | `/api/v1/users` | Create user |
| GET | `/api/v1/users/{id}` | Get user |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Deactivate (soft) |
| GET | `/api/v1/roles` | List roles |
| POST | `/api/v1/roles` | Create role |
| PUT | `/api/v1/roles/{id}/permissions` | Assign permissions to role |

---

### 7.5 Parties API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/parties` | List (filter by `?role=customer\|supplier`) |
| POST | `/api/v1/parties` | Create party + contacts + roles |
| GET | `/api/v1/parties/{id}` | Party detail |
| PUT | `/api/v1/parties/{id}` | Update |
| DELETE | `/api/v1/parties/{id}` | Soft delete |
| POST | `/api/v1/parties/{id}/contacts` | Add contact |
| POST | `/api/v1/parties/{id}/roles` | Add role |
| GET | `/api/v1/parties/{id}/statement` | Account statement by date range |

**Create payload:**
```json
{
  "display_name": "ABC Co.",
  "type": "company",
  "roles": ["customer"],
  "tax_number": "3000000000",
  "contacts": [
    { "name": "Ali", "email": "ali@abc.com", "phone": "+966500000001", "is_primary": true }
  ]
}
```

---

### 7.6 Invoices API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/invoices` | List invoices |
| POST | `/api/v1/invoices` | Create (Draft) |
| GET | `/api/v1/invoices/{id}` | Detail + lines + payment history |
| PUT | `/api/v1/invoices/{id}` | Update draft |
| DELETE | `/api/v1/invoices/{id}` | Delete draft |
| POST | `/api/v1/invoices/{id}/approve` | Approve → posts journal |
| POST | `/api/v1/invoices/{id}/cancel` | Cancel (creates reversal if approved) |
| GET | `/api/v1/invoices/{id}/pdf` | Download PDF |

**Query params:** `?type=sale\|purchase&status=draft\|approved\|paid\|overdue&party_id=&date_from=&date_to=`

**Create payload:**
```json
{
  "type": "sale",
  "party_id": "uuid",
  "issue_date": "2026-04-14",
  "due_date": "2026-04-28",
  "payment_term_id": "uuid",
  "currency_code": "SAR",
  "notes": "Payment due within 14 days.",
  "lines": [
    {
      "description": "Consulting services",
      "quantity": 10,
      "unit_price": 500.00,
      "discount_amount": 0,
      "tax_rate_id": "uuid",
      "product_id": null
    }
  ]
}
```

---

### 7.7 Payments API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/payments` | List payments |
| POST | `/api/v1/payments` | Record payment + allocate to invoice |
| GET | `/api/v1/payments/{id}` | Payment detail |

**Create payload:**
```json
{
  "party_id": "uuid",
  "invoice_id": "uuid",
  "direction": "inbound",
  "method": "bank_transfer",
  "amount": 5750.00,
  "currency_code": "SAR",
  "paid_at": "2026-04-14",
  "reference": "TRX-001"
}
```

---

### 7.8 Chart of Accounts API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/accounts` | Hierarchical accounts tree |
| POST | `/api/v1/accounts` | Create account |
| GET | `/api/v1/accounts/{id}` | Account detail |
| PUT | `/api/v1/accounts/{id}` | Update |
| DELETE | `/api/v1/accounts/{id}` | Delete (only if no journal lines) |
| GET | `/api/v1/accounts/{id}/statement` | Ledger by date range |

---

### 7.9 Accounting Reports API

All accept: `?date_from=&date_to=&format=json|pdf|excel`

| Endpoint | Report |
|----------|--------|
| `/api/v1/reports/trial-balance` | Trial balance |
| `/api/v1/reports/income-statement` | Profit & loss |
| `/api/v1/reports/balance-sheet` | Balance sheet |
| `/api/v1/reports/cash-flows` | Cash flow |
| `/api/v1/reports/ledger` | General ledger |
| `/api/v1/reports/receivables` | Customer receivables aging |
| `/api/v1/reports/payables` | Supplier payables aging |
| `/api/v1/reports/tax-return` | VAT (15%) tax return |
| `/api/v1/reports/daily` | Daily transactions |
| `/api/v1/reports/employee-salaries` | Salary summary |
| `/api/v1/reports/inventory-valuation` | Inventory valuation |
| `/api/v1/reports/commissions` | Commission report |

---

### 7.10 Inventory API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| CRUD | `/api/v1/products` | Product management |
| POST | `/api/v1/products/import` | Import from Excel |
| CRUD | `/api/v1/product-categories` | Category tree |
| CRUD | `/api/v1/units` | Units of measurement |
| CRUD | `/api/v1/warehouses` | Warehouse management |
| GET | `/api/v1/stock-balances` | Current balances |
| POST | `/api/v1/stock-movements` | Manual adjustment |
| POST | `/api/v1/stock-transfers` | Inter-warehouse transfer |
| CRUD | `/api/v1/stock-counts` | Physical count |
| POST | `/api/v1/stock-counts/{id}/approve` | Approve count |

---

### 7.11 HR API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| CRUD | `/api/v1/employees` | Employee management |
| POST | `/api/v1/employees/import` | Import from Excel |
| GET | `/api/v1/attendance` | List records |
| POST | `/api/v1/attendance` | Manual entry |
| POST | `/api/v1/attendance/import` | Import from device/Excel |
| GET | `/api/v1/attendance/report` | Monthly summary |
| CRUD | `/api/v1/leaves` | Leave requests |
| POST | `/api/v1/leaves/{id}/approve` | Approve leave |
| POST | `/api/v1/leaves/{id}/reject` | Reject leave |
| GET | `/api/v1/payroll-runs` | Payroll history |
| POST | `/api/v1/payroll-runs` | Generate run for period |
| GET | `/api/v1/payroll-runs/{id}` | Detail with lines |
| PUT | `/api/v1/payroll-runs/{id}/lines/{lineId}` | Manual adjustment |
| POST | `/api/v1/payroll-runs/{id}/approve` | Approve + post to Accounting |

---

### 7.12 Projects API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| CRUD | `/api/v1/projects` | Project management |
| POST | `/api/v1/projects/{id}/members` | Add member |
| CRUD | `/api/v1/projects/{id}/tasks` | Task management |
| PUT | `/api/v1/projects/{id}/tasks/{tid}/status` | Change task status |
| POST | `/api/v1/time-entries` | Log time |
| GET | `/api/v1/time-entries` | List (filter by project/user/date) |

---

### 7.13 Settings API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/settings/general` | Get org settings |
| PUT | `/api/v1/settings/general` | Update settings |
| CRUD | `/api/v1/settings/payment-terms` | Payment terms |
| CRUD | `/api/v1/settings/tax-rates` | Tax rates |
| CRUD | `/api/v1/settings/salary-components` | Payroll components |

---

## 8. Business Logic Rules

### Invoice
- Only `draft` invoices can be edited or deleted
- Approving posts immutable journal entries and fires `InvoiceApproved` event
- Cancellation of an approved invoice creates a reversing journal batch
- Grand total = subtotal − discount_total + tax_total
- At least one line item required

### Payment
- Payment amount cannot exceed outstanding balance on invoice
- Partial payment → `partially_paid` status; full allocation → `paid`
- Payment creates journal entries: DR Receivable / CR Cash
- Cannot be edited after creation; use a refund/reversal payment

### Inventory
- Stock cannot go negative unless warehouse policy explicitly allows it
- All stock changes go through `StockMovement` records
- Transfer creates outbound movement from source + inbound movement to destination in single transaction
- Only variances (≠ 0) generate movements on stock count approval

### Payroll
- Cannot approve a run if another approved/posted run exists for same period
- Approved payroll posts journal: DR Salary Expense / CR Salary Payable
- Lines cannot be modified after approval

### Multi-Tenancy
- Every model with `HasOrganization` trait auto-filters by `organization_id` globally
- Sanctum token carries the user's `organization_id` — no separate tenant ID header needed

---

## 9. Background Jobs & Events

### 9.1 Domain Events

| Event | Fired By | Listeners |
|-------|----------|-----------|
| `InvoiceApproved` | ApproveInvoiceAction | PostJournalListener, SendInvoiceNotification, AuditListener |
| `PaymentRecorded` | RecordPaymentAction | UpdateInvoiceStatusListener, AuditListener |
| `StockMoved` | CreateStockMovementAction | UpdateStockBalanceListener, LowStockAlertListener |
| `PayrollApproved` | ApprovePayrollRunAction | PostPayrollJournalListener, AuditListener |
| `LeaveApproved` | ApproveLeaveAction | NotifyEmployeeListener, AuditListener |
| `TaskCompleted` | UpdateTaskStatusAction | NotifyProjectManagerListener |
| `UserInvited` | InviteUserAction | SendInvitationEmailListener |

### 9.2 Queued Jobs

| Job | Trigger | Queue |
|-----|---------|-------|
| `GeneratePdfJob` | Invoice/report PDF requested | `pdf` queue |
| `SendEmailJob` | Any transactional email | `email` queue |
| `SendSmsJob` | OTP or notification SMS | `notifications` queue |
| `ExportExcelJob` | Large list export | `exports` queue |
| `ImportExcelJob` | Product/employee import | `imports` queue |
| `LowStockAlertJob` | StockMoved event, qty below alert | `notifications` queue |

### 9.3 Scheduled Tasks

```php
// app/Console/Kernel.php
$schedule->command('invoices:mark-overdue')->daily();           // mark past-due invoices
$schedule->command('reports:cache-daily-summary')->daily();     // cache dashboard KPIs
$schedule->command('storage:clean-temp-files')->weekly();       // clean up temp exports
```

---

## 10. External Integrations

### 10.1 SMS (Yamamah)

```php
// app/Modules/Core/Infrastructure/Integrations/SmsService.php
class SmsService
{
    public function send(string $phone, string $message): void
    {
        Http::post(config('erp.sms.api_url'), [
            'username'    => config('erp.sms.username'),
            'password'    => config('erp.sms.password'),
            'senderName'  => config('erp.sms.sender'),
            'Recivers'    => $phone,
            'MessageBody' => $message,
            'unicode'     => 'e',
        ]);
    }
}
```

### 10.2 WhatsApp (Meta Graph API)

```php
class WhatsAppService
{
    public function sendTemplate(string $phone, string $templateName, array $params): void
    {
        Http::withToken(config('erp.whatsapp.access_token'))
            ->post(config('erp.whatsapp.api_url') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => ['code' => 'ar'],
                    'components' => $params,
                ],
            ]);
    }
}
```

### 10.3 File Storage (S3-compatible)

```php
// All uploads via Laravel's Storage facade
Storage::disk('s3')->putFile('invoices/' . $invoice->id, $file);
$url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30));
```

Attachment metadata stored in `attachments` table. Actual files never stored locally in production.

### 10.4 PDF Generation

```php
// Option A: DomPDF (simpler, in-process)
$pdf = Pdf::loadView('pdf.invoice', compact('invoice'))->setPaper('a4');
return $pdf->stream('invoice.pdf');

// Option B: Puppeteer via Node sidecar (for pixel-perfect React templates)
// Dispatches GeneratePdfJob → calls Node service → stores result in S3
```

---

## 11. Authorization (Policies)

Each module has a Policy class per model, registered in `ModuleServiceProvider`.

```php
// app/Modules/Accounting/UI/Policies/InvoicePolicy.php
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices:read');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices:write');
    }

    public function approve(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices:approve') && $invoice->status === InvoiceStatus::Draft;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices:write') && $invoice->status === InvoiceStatus::Draft;
    }
}
```

Controllers use:
```php
$this->authorize('approve', $invoice);
// or
Gate::allows('invoices:approve')
```

---

## 12. Security

| Concern | Implementation |
|---------|---------------|
| Authentication | Sanctum sessions (web) + tokens (API) |
| Password hashing | Bcrypt (Laravel default) |
| CSRF | Sanctum CSRF cookie for session-based requests |
| Multi-tenancy isolation | OrganizationScope global scope on all models |
| SQL injection | Eloquent parameterized queries only |
| Rate limiting | Laravel throttle middleware on auth + sensitive endpoints |
| Sensitive data | Never log passwords; encrypt certificates at rest |
| Audit trail | ActivityLog on all financially sensitive operations |
| File uploads | Validate mime type + size; store on S3, never serve directly |
| API tokens | Sanctum with abilities (scoped tokens) |
| Secrets | All credentials in `.env`, never hardcoded |

---

## 13. Environment Variables

```env
# Application
APP_NAME="ERP V2"
APP_URL=https://erp.example.com
APP_ENV=production
APP_DEBUG=false
APP_KEY=

# Database
DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Queue
QUEUE_CONNECTION=redis
HORIZON_PREFIX=erp_

# Auth
SANCTUM_STATEFUL_DOMAINS=erp.example.com

# ERP-specific
ERP_DEFAULT_VAT_RATE=15
ERP_DEFAULT_CURRENCY=SAR
ERP_DEFAULT_LOCALE=ar

# File storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="ERP V2"

# SMS (Yamamah)
SMS_API_URL=https://api.yamamah.com/SendSMS
SMS_USERNAME=
SMS_PASSWORD=
SMS_SENDER_NAME=ERP

# WhatsApp (Meta)
WHATSAPP_API_URL=https://graph.facebook.com/v18.0/
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=

# Pusher / Soketi (Real-time)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

# Sentry
SENTRY_LARAVEL_DSN=
```

---

## 14. Testing Strategy

### 14.1 Test Types

| Type | Location | Covers |
|------|----------|--------|
| **Unit** | `tests/Unit/` | Actions, Services, TaxCalculator, Enums |
| **Feature** | `tests/Feature/` | Full HTTP cycle per endpoint (auth, validation, response) |
| **Integration** | `tests/Integration/` | DB-level: transactions, constraints, multi-tenant isolation |

### 14.2 Key Test Cases Per Module

**Accounting:**
- Invoice can only be approved when in Draft status
- Approving invoice creates correct journal entries
- Cancelling approved invoice creates reversal journal batch
- Payment allocation does not exceed invoice total

**Inventory:**
- Stock movement decrements stock_balance correctly
- Negative stock throws InsufficientStockException
- Stock count approval creates movements only for non-zero variances

**HR:**
- Payroll run generates correct net amount per employee
- Approving payroll creates journal batch in Accounting
- Duplicate payroll run for same period is rejected

**Multi-tenancy:**
- User from Org A cannot read resources from Org B

### 14.3 Test Helpers

```php
// RefreshDatabase + Organization + User factory in every Feature test
use RefreshDatabase;

protected function setUp(): void
{
    parent::setUp();
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create();
    $this->actingAs($this->user);
}
```

---

## 15. Implementation Roadmap

### Phase 0 — Foundation (Weeks 1–3)
- [ ] Laravel project scaffold with Modular structure
- [ ] PostgreSQL schema (Core + Parties tables)
- [ ] Sanctum auth (session + token)
- [ ] Role/Permission system + seeder
- [ ] OrganizationScope multi-tenancy
- [ ] ActivityLog system
- [ ] Inertia + React setup
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Docker Compose for local dev

### Phase 1 — Core Business (Weeks 4–8)
- [ ] Auth module (login, logout, me, invite user)
- [ ] Organization + Branch management
- [ ] Parties CRUD (customers, suppliers, agents)
- [ ] Dashboard KPI queries
- [ ] Settings module

### Phase 2 — Invoicing & Accounting (Weeks 9–16)
- [ ] Chart of Accounts
- [ ] Invoice CRUD + approve + cancel
- [ ] Tax calculation service
- [ ] Journal entries (auto + manual)
- [ ] Payments + allocation
- [ ] All 12+ accounting reports
- [ ] PDF generation for invoices

### Phase 3 — Inventory (Weeks 17–21)
- [ ] Products, categories, units
- [ ] Warehouses
- [ ] Stock movements + balance management
- [ ] Inter-warehouse transfers
- [ ] Physical stock counts + approval
- [ ] Excel import/export

### Phase 4 — HR & Payroll (Weeks 22–26)
- [ ] Employee management
- [ ] Attendance (manual + import)
- [ ] Leave requests + approval
- [ ] Salary components
- [ ] Payroll run generation + approval
- [ ] Payroll journal posting

### Phase 5 — Projects & Communication (Weeks 27–30)
- [ ] Projects + members
- [ ] Tasks (Kanban)
- [ ] Time entries
- [ ] In-app notifications (Pusher)
- [ ] Email notifications
- [ ] SMS integration (Yamamah)

### Phase 6 — Polish & Launch (Weeks 31–38)
- [ ] WhatsApp integration (Meta)
- [ ] Advanced reports + Excel/PDF export
- [ ] Performance testing + query optimization
- [ ] Security audit
- [ ] User acceptance testing (UAT)
- [ ] Production deployment + monitoring
- [ ] API documentation (Scribe/OpenAPI)

**Estimated:** ~38 weeks with 2–3 developers

---

## 16. Out of Scope for V1 Backend

- Microservices or service mesh
- ZATCA Phase 2 e-invoicing (deferred — requires cert management, XML signing, GAZT API)
- Multi-currency accounting (SAR only in V1)
- WhatsApp Business template management UI (API integration yes, builder no)
- Mobile app API (GPS attendance deferred)
- Marketplace / Tender / Auction module
- Wallet / Subscription / Membership billing
- Advanced budgeting and cost center reporting
- Asset depreciation scheduling
