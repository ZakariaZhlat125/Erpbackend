# وثيقة نظام ERP V2 - التوثيق الشامل باللغة العربية

**تاريخ الإعداد:** 24 أبريل 2026  
**الإصدار:** 2.0  
**الحالة:** نشط ومُحدّث

---

## المحتويات

1. [نظرة عامة](#overview)
2. [المعمارية](#architecture)
3. [الوحدات الرئيسية](#modules)
4. [قاعدة البيانات](#database)
5. [واجهات API](#api)
6. [المتطلبات الوظيفية](#requirements)

---

<a name="overview"></a>
## 1. نظرة عامة على النظام

### 1.1 الوصف

نظام ERP V2 هو حل متكامل لتخطيط موارد المؤسسات مبني بـ Laravel 12 و PostgreSQL. يتبع نمط **Modular Monolith** لتحقيق التوازن بين البساطة والمرونة.

### 1.2 الأهداف

- إدارة شاملة لعمليات المؤسسة
- دعم تعدد المستأجرين (Multi-tenant)
- محاسبة متوافقة مع المعايير الدولية (IFRS/GAAP)
- واجهة متعددة اللغات سهلة الاستخدام
- قابلية التوسع والتطوير

### 1.3 المبادئ الأساسية

- **Thin Controllers**: كنترولرز خفيفة تفوّض للخدمات
- **Business Logic في Services**: منطق العمل منفصل
- **Immutable Ledger**: القيود المحاسبية غير قابلة للحذف
- **Multi-tenant بالافتراض**: عزل تلقائي بـ `organization_id``
- **Audit Trail**: تسجيل جميع العمليات الحساسة

---

<a name="architecture"></a>
## 2. معمارية النظام

### 2.1 التقنيات الأساسية

| المكون | التقنية |
|--------|---------|
| Framework | Laravel 12.x |
| لغة البرمجة | PHP 8.3+ |
| قاعدة البيانات | PostgreSQL 13+ |
| Cache/Queue | Redis |
| المصادقة | Laravel Sanctum |
| الصلاحيات | Spatie Permission |

### 2.2 البنية العامة

```
Laravel Application
├── قاعدة بيانات PostgreSQL واحدة
├── 6 وحدات رئيسية
│   ├── Core (الإدارة)
│   ├── Organization (المؤسسات)
│   ├── Parties (الأطراف)
│   ├── Accounting (المحاسبة)
│   ├── Inventory (المخزون)
│   ├── HR (الموارد البشرية)
│   └── Projects (المشاريع)
└── بنية تحتية مشتركة
```

### 2.3 دورة حياة الطلب

```
HTTP Request → Route → Validation → Authorization
    → Controller → Service → Repository → Model
    → Events → ActivityLog → Response
```

---

<a name="modules"></a>
## 3. الوحدات الرئيسية

### 3.1 وحدة الإدارة الأساسية (Core)

**المسؤولية:** المصادقة، الأدوار، الصلاحيات، الإعدادات

**الموديلات:**
- `User` - المستخدمون
- `Role` - الأدوار
- `Permission` - الصلاحيات
- `Setting` - الإعدادات
- `ActivityLog` - سجل الأنشطة

**الوظائف الرئيسية:**
- تسجيل الدخول/الخروج
- إدارة المستخدمين
- إدارة الأدوار والصلاحيات
- الإعدادات العامة
- تتبع النشاطات

---

### 3.2 وحدة المؤسسات (Organization)

**المسؤولية:** إدارة المؤسسات والفروع والأطراف

**الموديلات:**
- `Organization` - المؤسسات
- `Branch` - الفروع
- `Party` - الأطراف (عملاء/موردين)
- `PartyContact` - جهات الاتصال
- `PartyAddress` - العناوين
- `PaymentTerm` - شروط الدفع

**الوظائف الرئيسية:**
- إدارة المؤسسات (Multi-tenant root)
- إدارة الفروع
- إدارة العملاء والموردين
- إدارة جهات الاتصال
- كشف حساب العملاء/الموردين

---

### 3.3 وحدة المحاسبة (Accounting)

**المسؤولية:** دليل الحسابات، الفواتير، المدفوعات، القيود

**الموديلات:**
- `Account` - الحسابات
- `Invoice` - الفواتير
- `InvoiceLine` - بنود الفاتورة
- `Payment` - المدفوعات
- `JournalBatch` - دفعة القيود
- `JournalLine` - سطر القيد
- `TaxRate` - معدلات الضرائب

**الوظائف الرئيسية:**

#### الفواتير
- إنشاء فواتير المبيعات/المشتريات
- اعتماد الفواتير (ينشئ قيود محاسبية)
- إلغاء الفواتير (قيد عكسي)
- تكرار الفواتير
- البحث المتقدم
- الإحصائيات
- **جديد**: اعتماد جماعي
- **جديد**: حذف جماعي للمسودات

#### المدفوعات
- تسجيل المدفوعات الواردة/الصادرة
- ربط المدفوعات بالفواتير
- تحديث حالة الفواتير تلقائياً

#### القيود المحاسبية
- إنشاء قيود يدوية
- قيود تلقائية عند اعتماد الفواتير
- قيود الرواتب
- **قاعدة مهمة**: القيود المعتمدة لا تُحذف أبداً

---

### 3.4 وحدة المخزون (Inventory)

**المسؤولية:** المنتجات، المستودعات، حركات المخزون

**الموديلات:**
- `Product` - المنتجات
- `ProductCategory` - تصنيفات المنتجات
- `Unit` - وحدات القياس
- `Warehouse` - المستودعات
- `StockBalance` - أرصدة المخزون
- `StockMovement` - حركات المخزون
- `StockCount` - الجرد

**الوظائف الرئيسية:**

#### المنتجات
- إدارة المنتجات والخدمات
- التصنيفات الهرمية
- الأسعار (تكلفة/بيع)
- **جديد**: تحديث جماعي للأسعار
- **جديد**: تفعيل/إلغاء جماعي
- **جديد**: تنبيه المخزون المنخفض
- **جديد**: إحصائيات المخزون

#### حركات المخزون
- إدخال/إخراج
- التحويل بين المستودعات
- التسوية
- منع المخزون السالب

#### الجرد
- جرد دوري
- مقارنة الفعلي مع النظامي
- اعتماد الفروقات

---

### 3.5 وحدة الموارد البشرية (HR)

**المسؤولية:** الموظفين، الحضور، الإجازات، الرواتب

**الموديلات:**
- `Employee` - الموظفون
- `AttendanceRecord` - الحضور
- `LeaveRequest` - الإجازات
- `SalaryComponent` - مكونات الراتب
- `PayrollRun` - دورة الرواتب
- `PayrollLine` - تفاصيل راتب الموظف

**الوظائف الرئيسية:**

#### الموظفين
- ملفات الموظفين
- البيانات الوظيفية
- الهيكل التنظيمي
- **جديد**: تحديث جماعي للحالات
- **جديد**: مخطط الهيكل التنظيمي
- **جديد**: إحصائيات الموظفين

#### الحضور
- تسجيل الحضور والانصراف
- حساب ساعات العمل
- تقارير الحضور

#### الرواتب
- حساب الرواتب شهرياً
- البدلات والخصومات
- **ربط تلقائي مع المحاسبة**

---

### 3.6 وحدة المشاريع (Projects)

**المسؤولية:** إدارة المشاريع، المهام، الوقت

**الموديلات:**
- `Project` - المشاريع
- `ProjectMember` - أعضاء المشروع
- `Task` - المهام
- `TimeEntry` - تسجيل الوقت

**الوظائف الرئيسية:**

#### المشاريع
- إنشاء وإدارة المشاريع
- تعيين فريق العمل
- متابعة التقدم
- **جديد**: لوحة تحكم المشروع
- **جديد**: تحديث نسبة الإنجاز
- **جديد**: إحصائيات المشاريع

#### المهام
- إنشاء وتعيين المهام
- تحديث الحالات
- الأولويات
- تسجيل الوقت

---

<a name="database"></a>
## 4. قاعدة البيانات

### 4.1 المعلومات العامة

- **النوع:** PostgreSQL 13+
- **الترميز:** UTF-8
- **التوقيت:** UTC (يُحوّل حسب توقيت المؤسسة)
- **عدد الجداول:** 40+ جدول

### 4.2 الجداول الرئيسية

#### الإدارة الأساسية (10 جداول)
```
organizations, branches, users, roles, permissions,
model_has_roles, role_has_permissions, settings,
activity_logs, attachments
```

#### المحاسبة (8 جداول)
```
accounts, tax_rates, invoices, invoice_lines,
journal_batches, journal_lines, payments,
payment_allocations
```

#### المخزون (8 جداول)
```
product_categories, units, products, warehouses,
stock_balances, stock_movements, stock_counts,
stock_count_lines
```

#### الموارد البشرية (5 جداول)
```
employees, attendance_records, leave_requests,
payroll_runs, payroll_lines
```

#### المشاريع (4 جداول)
```
projects, project_members, tasks, time_entries
```

---

<a name="api"></a>
## 5. واجهات API

### 5.1 المعلومات العامة

- **الإصدار:** v1
- **URL الأساسي:** `/api/v1/`
- **الصيغة:** JSON فقط
- **المصادقة:** Bearer Token (Sanctum)
- **الترقيم:** 15 عنصر افتراضياً

### 5.2 نقاط النهاية الرئيسية

#### المصادقة
```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
```

#### الفواتير (45+ endpoint)
```
GET    /api/v1/accounting/invoices/statistics
GET    /api/v1/accounting/invoices/search
POST   /api/v1/accounting/invoices/bulk-approve
POST   /api/v1/accounting/invoices/bulk-delete
POST   /api/v1/accounting/invoices/{id}/duplicate
GET    /api/v1/accounting/invoices/export
```

#### المنتجات (30+ endpoint)
```
GET    /api/v1/inventory/products/statistics
GET    /api/v1/inventory/products/search
GET    /api/v1/inventory/products/low-stock
POST   /api/v1/inventory/products/bulk-update-prices
POST   /api/v1/inventory/products/bulk-activate
GET    /api/v1/inventory/products/export
```

#### الموظفين (25+ endpoint)
```
GET    /api/v1/hr/employees/statistics
GET    /api/v1/hr/employees/search
GET    /api/v1/hr/employees/org-chart
POST   /api/v1/hr/employees/bulk-update-status
GET    /api/v1/hr/employees/export
```

---

<a name="requirements"></a>
## 6. المتطلبات الوظيفية

### 6.1 العمليات الجماعية (Bulk Operations)

#### FR-BULK-001: اعتماد فواتير متعددة
**الوصف:** إمكانية اعتماد عدة فواتير دفعة واحدة  
**المدخلات:** `invoice_ids[]`  
**الخرج:** عدد المعتمد/الفاشل مع التفاصيل  
**القيود:** فقط الفواتير بحالة "مسودة"

#### FR-BULK-002: تحديث أسعار متعددة
**الوصف:** تحديث أسعار عدة منتجات  
**المدخلات:** `product_ids[]`, نسبة أو قيمة ثابتة  
**الخرج:** عدد المُحدّث  
**الأنواع:** تكلفة، بيع، أو كلاهما

#### FR-BULK-003: تحديث حالات موظفين
**الوصف:** تغيير حالة عدة موظفين  
**المدخلات:** `employee_ids[]`, الحالة الجديدة  
**الخيارات:** نشط، غير نشط، منتهي الخدمة

### 6.2 البحث المتقدم

#### FR-SEARCH-001: بحث ديناميكي
**المعايير المدعومة:**
- تطابق تام: `field=value`
- مصفوفة: `field=[value1,value2]`
- نطاق: `field_from`, `field_to`
- نص جزئي: `field_like`

**مثال للفواتير:**
```
GET /api/v1/accounting/invoices/search?
    status=approved&
    issue_date_from=2026-01-01&
    issue_date_to=2026-12-31&
    grand_total_from=1000
```

### 6.3 الإحصائيات والتحليلات

#### FR-STATS-001: إحصائيات الفواتير
```json
{
  "total_count": 150,
  "draft_count": 20,
  "approved_count": 80,
  "paid_count": 45,
  "total_amount": 1500000.00,
  "monthly_trends": [...]
}
```

#### FR-STATS-002: إحصائيات المخزون
```json
{
  "total_count": 500,
  "active_count": 480,
  "total_inventory_value": 2500000.00,
  "by_category": [...]
}
```

### 6.4 قواعد العمل الأساسية

#### BR-001: الفواتير
- المسودة فقط قابلة للتعديل/الحذف
- الاعتماد ينشئ قيود محاسبية تلقائياً
- الإلغاء ينشئ قيد عكسي

#### BR-002: المخزون
- المخزون السالب ممنوع افتراضياً
- كل حركة تُسجل في StockMovement
- الجرد يُحدّث عند الاعتماد فقط

#### BR-003: Multi-Tenancy
- كل استعلام يُرشح بـ `organization_id` تلقائياً
- عزل تام بين المؤسسات
- لا يمكن الوصول لبيانات مؤسسة أخرى

---

## 7. الميزات الحديثة المضافة (45+ وظيفة)

### 7.1 طبقة Repository
- `findByIds()` - جلب عناصر متعددة
- `updateMany()` - تحديث جماعي
- `search()` - بحث ديناميكي

### 7.2 الإحصائيات
- إحصائيات شاملة لكل وحدة
- اتجاهات شهرية
- تحليلات بيانية

### 7.3 العمليات الجماعية
- اعتماد/حذف متعدد
- تحديث جماعي
- تفعيل/إلغاء متعدد

---

## 8. الأمان والصلاحيات

### 8.1 المصادقة
- Laravel Sanctum
- Session للواجهة
- Token للـ API

### 8.2 الصلاحيات
```
{module}:read    - القراءة
{module}:write   - الكتابة
{module}:delete  - الحذف
{module}:approve - الاعتماد (خاص)
```

---

**نهاية الوثيقة**

> للمزيد من التفاصيل، راجع:
> - `BACKEND_DOCUMENTATION.md` (إنجليزي)
> - `DATABASE_SCHEMA.md` (تفاصيل قاعدة البيانات)
> - `API_DOCUMENTATION.md` (تفاصيل API)
> - `IMPLEMENTATION_SUMMARY.md` (ملخص التنفيذ)
