# ERP V2 - API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication

All API endpoints require authentication using Laravel Sanctum.

### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@democompany.com",
  "password": "password"
}

Response:
{
  "token": "1|...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@democompany.com",
    "organization_id": 1
  }
}
```

### Using Authentication Token
Include the token in all subsequent requests:
```http
Authorization: Bearer {token}
```

---

## Organizations API

### List Organizations
```http
GET /api/v1/organizations
Authorization: Bearer {token}
Permission: settings:read

Response:
{
  "data": [
    {
      "id": 1,
      "name": "Demo Company",
      "legal_name": "Demo Company LLC",
      "tax_number": "300000000000003",
      "base_currency": "SAR",
      "status": "active"
    }
  ]
}
```

### Get Organization
```http
GET /api/v1/organizations/{id}
Permission: settings:read
```

### Create Organization
```http
POST /api/v1/organizations
Permission: settings:write

{
  "name": "New Company",
  "legal_name": "New Company LLC",
  "tax_number": "123456789",
  "base_currency": "SAR",
  "timezone": "Asia/Riyadh",
  "locale": "ar"
}
```

### Update Organization
```http
PUT /api/v1/organizations/{id}
Permission: settings:write
```

### Delete Organization
```http
DELETE /api/v1/organizations/{id}
Permission: settings:write
```

---

## Branches API

### List Branches
```http
GET /api/v1/branches
Permission: settings:read
```

### Create Branch
```http
POST /api/v1/branches
Permission: settings:write

{
  "organization_id": 1,
  "name": "Main Branch",
  "code": "MAIN",
  "address": "King Fahd Road, Riyadh",
  "phone": "+966112345678",
  "is_active": true
}
```

---

## Parties API (Customers/Suppliers)

### List Parties
```http
GET /api/v1/parties
Permission: parties:read

Query Parameters:
- role: customer|supplier|agent|contractor
- is_active: true|false
- search: search term

Response:
{
  "data": [
    {
      "id": 1,
      "code": "PARTY001",
      "display_name": "ABC Company",
      "type": "company",
      "is_active": true,
      "roles": [
        {"role": "customer"}
      ]
    }
  ]
}
```

### Get Party
```http
GET /api/v1/parties/{id}
Permission: parties:read
```

### Create Party
```http
POST /api/v1/parties
Permission: parties:write

{
  "code": "PARTY001",
  "type": "company",
  "display_name": "ABC Company",
  "legal_name": "ABC Company LLC",
  "tax_number": "123456789",
  "default_currency": "SAR",
  "is_active": true
}
```

### Update Party
```http
PUT /api/v1/parties/{id}
Permission: parties:write
```

### Delete Party
```http
DELETE /api/v1/parties/{id}
Permission: parties:delete
```

---

## Invoices API

### List Invoices
```http
GET /api/v1/invoices
Permission: invoices:read

Query Parameters:
- type: sale|purchase
- status: draft|approved|paid
- party_id: filter by party
- date_from: YYYY-MM-DD
- date_to: YYYY-MM-DD

Response:
{
  "data": [
    {
      "id": 1,
      "number": "INV-001",
      "type": "sale",
      "status": "approved",
      "issue_date": "2026-04-19",
      "due_date": "2026-05-19",
      "grand_total": "11500.00",
      "party": {
        "id": 1,
        "display_name": "ABC Company"
      }
    }
  ]
}
```

### Get Invoice
```http
GET /api/v1/invoices/{id}
Permission: invoices:read
```

### Create Invoice
```http
POST /api/v1/invoices
Permission: invoices:write

{
  "type": "sale",
  "party_id": 1,
  "issue_date": "2026-04-19",
  "due_date": "2026-05-19",
  "currency_code": "SAR",
  "status": "draft",
  "lines": [
    {
      "product_id": 1,
      "description": "Product A",
      "quantity": 10,
      "unit_price": 100.00,
      "discount_amount": 0,
      "tax_rate_id": 1
    }
  ]
}
```

### Update Invoice
```http
PUT /api/v1/invoices/{id}
Permission: invoices:write
```

### Approve Invoice
```http
POST /api/v1/invoices/{id}/approve
Permission: invoices:approve

Creates journal entries and changes status to 'approved'
```

### Cancel Invoice
```http
POST /api/v1/invoices/{id}/cancel
Permission: invoices:cancel

Creates reversal journal entries if already approved
```

### Delete Invoice (Draft Only)
```http
DELETE /api/v1/invoices/{id}
Permission: invoices:delete
```

---

## Products API

### List Products
```http
GET /api/v1/products
Permission: products:read

Query Parameters:
- type: product|service
- is_active: true|false
- category_id: filter by category
- search: search in name, sku, description

Response:
{
  "data": [
    {
      "id": 1,
      "sku": "PROD001",
      "name": "Product A",
      "type": "product",
      "cost_price": "80.00",
      "selling_price": "100.00",
      "track_inventory": true,
      "is_active": true,
      "total_stock": 150
    }
  ]
}
```

### Get Product
```http
GET /api/v1/products/{id}
Permission: products:read
```

### Create Product
```http
POST /api/v1/products
Permission: products:write

{
  "sku": "PROD001",
  "type": "product",
  "name": "Product A",
  "description": "Description here",
  "category_id": 1,
  "unit_id": 1,
  "cost_price": 80.00,
  "selling_price": 100.00,
  "tax_rate_id": 1,
  "track_inventory": true,
  "is_active": true
}
```

### Update Product
```http
PUT /api/v1/products/{id}
Permission: products:write
```

### Delete Product
```http
DELETE /api/v1/products/{id}
Permission: products:delete
```

---

## Accounts API (Chart of Accounts)

### List Accounts
```http
GET /api/v1/accounts
Permission: accounts:read

Query Parameters:
- type: asset|liability|equity|revenue|expense
- is_active: true|false

Response:
{
  "data": [
    {
      "id": 1,
      "code": "1000",
      "name": "Assets",
      "type": "asset",
      "level": 1,
      "is_active": true
    }
  ]
}
```

### Get Account
```http
GET /api/v1/accounts/{id}
Permission: accounts:read
```

### Create Account
```http
POST /api/v1/accounts
Permission: accounts:write

{
  "code": "1100",
  "name": "Current Assets",
  "type": "asset",
  "parent_id": 1,
  "level": 2,
  "allow_manual_entries": true,
  "is_active": true
}
```

---

## Payments API

### List Payments
```http
GET /api/v1/payments
Permission: payments:read

Query Parameters:
- direction: inbound|outbound
- party_id: filter by party
- invoice_id: filter by invoice
- date_from: YYYY-MM-DD
- date_to: YYYY-MM-DD
```

### Get Payment
```http
GET /api/v1/payments/{id}
Permission: payments:read
```

### Create Payment
```http
POST /api/v1/payments
Permission: payments:write

{
  "party_id": 1,
  "invoice_id": 1,
  "direction": "inbound",
  "method": "bank_transfer",
  "amount": 11500.00,
  "currency_code": "SAR",
  "paid_at": "2026-04-19",
  "reference": "REF-001",
  "notes": "Payment received"
}
```

---

## Warehouses API

### List Warehouses
```http
GET /api/v1/warehouses
Permission: warehouses:read
```

### Create Warehouse
```http
POST /api/v1/warehouses
Permission: warehouses:write

{
  "branch_id": 1,
  "name": "Main Warehouse",
  "code": "WH001",
  "address": "Industrial Area, Riyadh",
  "manager_user_id": 1,
  "allow_negative_stock": false,
  "is_active": true
}
```

---

## Employees API

### List Employees
```http
GET /api/v1/employees
Permission: employees:read

Query Parameters:
- status: active|inactive|terminated
- branch_id: filter by branch
- department_name: filter by department
- search: search in name, employee_number, email

Response:
{
  "data": [
    {
      "id": 1,
      "employee_number": "EMP001",
      "full_name": "John Doe",
      "email": "john@example.com",
      "job_title": "Developer",
      "department_name": "IT",
      "status": "active",
      "base_salary": "5000.00"
    }
  ]
}
```

### Get Employee
```http
GET /api/v1/employees/{id}
Permission: employees:read
```

### Create Employee
```http
POST /api/v1/employees
Permission: employees:write

{
  "branch_id": 1,
  "employee_number": "EMP001",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+966500000001",
  "hire_date": "2026-01-01",
  "job_title": "Developer",
  "department_name": "IT",
  "status": "active",
  "base_salary": 5000.00,
  "currency_code": "SAR"
}
```

### Update Employee
```http
PUT /api/v1/employees/{id}
Permission: employees:write
```

### Delete Employee
```http
DELETE /api/v1/employees/{id}
Permission: employees:delete
```

---

## Projects API

### List Projects
```http
GET /api/v1/projects
Permission: projects:read

Query Parameters:
- status: planning|active|on_hold|completed|cancelled
- party_id: filter by party
```

### Get Project
```http
GET /api/v1/projects/{id}
Permission: projects:read
```

### Create Project
```http
POST /api/v1/projects
Permission: projects:write

{
  "code": "PROJ001",
  "name": "Website Redesign",
  "description": "Complete website redesign project",
  "party_id": 1,
  "status": "planning",
  "start_date": "2026-05-01",
  "end_date": "2026-08-31",
  "budget_amount": 50000.00,
  "progress_percent": 0
}
```

### Update Project
```http
PUT /api/v1/projects/{id}
Permission: projects:write
```

### Delete Project
```http
DELETE /api/v1/projects/{id}
Permission: projects:delete
```

---

## Tasks API

### List Tasks
```http
GET /api/v1/tasks
Permission: tasks:read

Query Parameters:
- status: todo|in_progress|blocked|done|cancelled
- priority: low|medium|high|urgent
- project_id: filter by project
- assigned_user_id: filter by assigned user
```

### Get Task
```http
GET /api/v1/tasks/{id}
Permission: tasks:read
```

### Create Task
```http
POST /api/v1/tasks
Permission: tasks:write

{
  "project_id": 1,
  "title": "Design homepage mockup",
  "description": "Create homepage design mockup in Figma",
  "status": "todo",
  "priority": "high",
  "assigned_user_id": 1,
  "due_date": "2026-05-15",
  "estimated_minutes": 480
}
```

### Update Task
```http
PUT /api/v1/tasks/{id}
Permission: tasks:write
```

### Delete Task
```http
DELETE /api/v1/tasks/{id}
Permission: tasks:delete
```

---

## Permissions System

All endpoints use Spatie Laravel Permission package for authorization.

### Available Permissions

**Users & Roles:**
- `users:read`, `users:write`, `users:delete`
- `roles:read`, `roles:write`, `roles:manage_permissions`

**Parties:**
- `parties:read`, `parties:write`, `parties:delete`

**Invoices:**
- `invoices:read`, `invoices:write`, `invoices:approve`, `invoices:cancel`, `invoices:delete`

**Payments:**
- `payments:read`, `payments:write`

**Accounts:**
- `accounts:read`, `accounts:write`, `accounts:delete`

**Journals:**
- `journals:read`, `journals:write`, `journals:post`

**Products:**
- `products:read`, `products:write`, `products:delete`

**Warehouses:**
- `warehouses:read`, `warehouses:write`

**Stock:**
- `stock:read`, `stock:adjust`, `stock:count`, `stock:approve_count`

**Employees:**
- `employees:read`, `employees:write`, `employees:delete`

**Attendance:**
- `attendance:read`, `attendance:write`

**Leaves:**
- `leaves:read`, `leaves:write`, `leaves:approve`

**Payroll:**
- `payroll:read`, `payroll:write`, `payroll:approve`

**Projects:**
- `projects:read`, `projects:write`, `projects:delete`

**Tasks:**
- `tasks:read`, `tasks:write`, `tasks:delete`

**Time Entries:**
- `time_entries:read`, `time_entries:write`

**Settings:**
- `settings:read`, `settings:write`

**Audit:**
- `audit:read`

**Reports:**
- `reports:financial`, `reports:inventory`, `reports:hr`

---

## Multi-Tenancy

All API endpoints automatically filter data by `organization_id` based on the authenticated user's organization. You cannot access data from other organizations.

### Global Scopes Applied:
- All models automatically filter by `organization_id`
- Creating new records automatically sets `organization_id` from authenticated user
- All queries are scoped to the current user's organization

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

---

## Testing with Postman

1. **Import Collection**: Create a new Postman collection
2. **Set Environment Variables**:
   - `base_url`: `http://localhost:8000/api/v1`
   - `token`: (set after login)

3. **Login Request**:
```
POST {{base_url}}/auth/login
Body (JSON):
{
  "email": "admin@democompany.com",
  "password": "password"
}
```

4. **Save Token**: From response, save token to environment variable

5. **Subsequent Requests**: Add to Headers:
```
Authorization: Bearer {{token}}
```

---

## Development Setup

1. Run migrations:
```bash
php artisan migrate:fresh --seed
```

2. Start development server:
```bash
php artisan serve
```

3. Test API endpoint:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@democompany.com","password":"password"}'
```

---

**Last Updated**: April 19, 2026  
**API Version**: v1  
**Laravel Version**: 11.x
