# تحليل نظام ERP متعدد المستأجرين (SaaS Multi-Tenant)

> **المشروع:** نظام ERP سحابي متعدد المستأجرين  
> **التقنية:** Laravel 11 (PHP 8.2+)  
> **المصادقة:** Laravel Sanctum  
> **الصلاحيات:** Spatie Permission  
> **تاريخ التحليل:** مايو 2026

---

## 🧭 توصيف المشروع

### ما هو هذا النظام؟

**MiddleEast ERP** هو نظام تخطيط موارد المؤسسات (ERP) سحابي متكامل، مبني على نموذج **SaaS متعدد المستأجرين (Multi-Tenant)**، يُتيح لعدد غير محدود من الشركات والمؤسسات إدارة عملياتها اليومية بالكامل من منصة واحدة آمنة ومعزولة.

يختلف هذا النظام عن أنظمة ERP التقليدية في كونه **سحابياً بالكامل**؛ لا يحتاج العميل إلى شراء خوادم أو برامج، بل يشترك في خطة شهرية أو سنوية ويبدأ العمل فوراً من أي جهاز وأي مكان.

---

### 🎯 المشكلة التي يحلها النظام

تعاني كثير من الشركات في منطقة الشرق الأوسط من:

| المشكلة | الحل الذي يقدمه النظام |
|---------|----------------------|
| تشتت البيانات بين أنظمة متعددة غير متكاملة | منصة واحدة تجمع المحاسبة والمخزون والموارد البشرية والمشاريع |
| أنظمة ERP تقليدية مكلفة جداً وتحتاج بنية تحتية ضخمة | نموذج SaaS بتكلفة شهرية منخفضة بدون أي بنية تحتية |
| صعوبة التوسع عند نمو الأعمال | هيكلية مرنة تدعم الفروع المتعددة والمستخدمين الإضافيين |
| غياب سلاسل الاعتماد والموافقات الرسمية | محرك سير عمل قابل للتخصيص لكل نوع من المعاملات |
| صعوبة متابعة الموظفين والرواتب | وحدة HR متكاملة مع الحضور والرواتب والإجازات |
| ضعف الرقابة والمساءلة | سجل نشاطات شامل يتتبع كل عملية مع IP والمستخدم |

---

### ⚙️ كيف يعمل النظام؟

```
┌─────────────────────────────────────────────────────────────────┐
│                        طبقة العملاء (Clients)                   │
│         متصفح ويب │ تطبيق جوال │ نظام خارجي عبر API          │
└──────────────────────────────┬──────────────────────────────────┘
                               │ HTTPS / REST API
┌──────────────────────────────▼──────────────────────────────────┐
│                     طبقة المصادقة والأمان                      │
│        Laravel Sanctum (Token) │ RBAC (Spatie Permissions)      │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│                       طبقة الأعمال (Modules)                    │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│  │ محاسبة  │  │     مخزون  │  │   HR     │  │ مشاريع  │           │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘         │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│  │ منظمات │  │اشتراكات │  │  سير عمل │  │  جدول   │           │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘         │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│                        طبقة البيانات                            │
│              MySQL/PostgreSQL │ كل مستأجر معزول بـ org_id       │
└─────────────────────────────────────────────────────────────────┘
```

**مبدأ العزل:** كل شركة مستأجرة تملك بياناتها المعزولة تماماً. لا يمكن لأي مستخدم الوصول إلى بيانات شركة أخرى حتى لو كان على نفس الخادم.

---

### 🏗️ البنية التقنية

| الطبقة | التقنية | الغرض |
|--------|---------|-------|
| **الواجهة الخلفية** | Laravel 11 (PHP 8.2+) | معالجة الطلبات وقواعد الأعمال |
| **قاعدة البيانات** | MySQL / PostgreSQL | تخزين البيانات |
| **المصادقة** | Laravel Sanctum | إصدار وإدارة الـ Tokens |
| **الصلاحيات** | Spatie Laravel Permission | RBAC معزول لكل منظمة |
| **المعالجة الخلفية** | Laravel Queue + Jobs | الإشعارات والتصدير والعمليات الثقيلة |
| **التخزين المؤقت** | Redis / File Cache | تسريع الاستعلامات المتكررة |
| **الملفات** | Laravel Storage | رفع المستندات والصور |
| **التوثيق** | Swagger / OpenAPI | توثيق الـ API |

---

### 🌐 نموذج الاشتراك (SaaS Tiers)

```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│    مجاني     │    أساسي     │   احترافي    │   مؤسسي      │
│   (تجريبي)  │              │              │              │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ 14 يوم       │ شهري / سنوي │ شهري / سنوي │  سنوي فقط   │
│ 2 مستخدمين  │ 10 مستخدمين │ 50 مستخدماً │ غير محدود   │
│ فرع وا   │ فرعان        │ 10 فروع      │    غير محدود   │
│ محاسبة أساسية     + مخزون │ + HR + مشاريع│ جميع الوحدات│
└──────────────┴──────────────┴──────────────┴──────────────┘
```

---

## 👥 الفئة المستهدفة

### الفئة الأساسية (Primary Target)

#### 1. الشركات الصغيرة والمتوسطة (SMEs) - الخليج العربي

**الحجم:** من 5 إلى 500 موظف  
**القطاعات المستهدفة:**

| القطاع | الوحدات الأكثر استخداماً |
|--------|-------------------------|
| **تجارة التجزئة والجملة** | مخزون + فواتير + نقاط بيع |
| **المقاولات والإنشاءات** | مشاريع + HR + تكاليف |
| **الخدمات المهنية** | فواتير + مشاريع + تتبع الوقت |
| **التوزيع واللوجستيات** | مخزون + مستودعات + تحويلات |
| **التصنيع الخفيف** | مخزون + مواد خام + إنتاج |
| **شركات التقنية والاستشارات** | مشاريع + HR + فواتير |
| **المطاعم والضيافة** | POS + مخزون + رواتب |

---

#### 2. الشركات الناشئة (Startups)

**لماذا هذا النظام مناسب لهم؟**
- لا حاجة لاستثمار مبكر في برامج مرخصة غالية
- يبدأون بخطة أساسية ويترقون عند النمو
- يوفر لهم بنية مالية ومحاسبية احترافية منذ اليوم الأول
- يساعدهم على الامتثال للمتطلبات الضريبية (VAT / ZATCA)

---

#### 3. المجموعات والشركات متعددة الفروع

**لماذا هذا النظام مناسب لهم؟**
- إدارة فروع متعددة من لوحة تحكم واحدة
- فصل البيانات المحاسبية بين الفروع مع تقارير موحدة
- تحويلات المخزون بين المستودعات مع سير موافقات
- مسير رواتب موحد لجميع الفروع

---

### الفئة الثانوية (Secondary Target)

#### 4. مقدمو خدمات ERP (شركاء التوزيع)

شركات تقنية تريد تقديم حل ERP جاهز لعملائها تحت علامتها التجارية **(White Label)** دون الحاجة لتطوير نظام من الصفر.

#### 5. المحاسبون القانونيون ومكاتب المحاسبة

يمكنهم إدارة حسابات عدة عملاء من نظام واحد، حيث كل عميل = مستأجر معزول بصلاحيات محددة.

---

### 🗺️ الأسواق الجغرافية المستهدفة

| السوق | الأولوية | السبب |
|-------|----------|-------|
| **المملكة العربية السعودية** | 🔴 عالية جداً | أكبر اقتصاد + إلزامية ZATCA + Vision 2030 |
| **الإمارات العربية المتحدة** | 🔴 عالية جداً | مركز أعمال إقليمي + تنوع اقتصادي |
| **الكويت** | 🟠 عالية | اقتصاد قوي + تحول رقمي |
| **قطر** | 🟠 عالية | نمو اقتصادي + مشاريع كبرى |
| **البحرين** | 🟡 متوسطة | مركز مالي + بيئة ترخيص مرنة |
| **الأردن ومصر** | 🟡 متوسطة | كثافة شركات صغيرة ومتوسطة |
| **العراق والجزائر والمغرب** | 🟢 مستقبلية | أسواق ناشئة بنمو متسارع |

---

### 💼 القيمة المقدمة لكل فئة

```
للشركة الصغيرة:
├── توفير 70% من تكلفة ERP التقليدي
├── بدء العمل خلال ساعات لا أسابيع
├── لا تحتاج فريق IT داخلي
└── امتثال ضريبي تلقائي

للشركة المتوسطة:
├── رؤية شاملة لكامل العمليات
├── تقارير مالية دقيقة ولحظية
├── تحكم دقيق بالصلاحيات
└── محرك موافقات قابل للتخصيص

لمجموعة الشركات:
├── لوحة تحكم موحدة لجميع الشركات
├── تقارير مجمعة عبر الفروع
├── فصل كامل بين بيانات كل شركة
└── رواتب وموارد بشرية موحدة
```

---

## 📋 جدول المحتويات

1. [المتطلبات الوظيفية](#1-المتطلبات-الوظيفية)
2. [المتطلبات غير الوظيفية](#2-المتطلبات-غير-الوظيفية)
3. [مخطط حالات الاستخدام](#3-مخطط-حالات-الاستخدام)
4. [مخطط الفئات](#4-مخطط-الفئات)
5. [دراسة الجدوى](#5-دراسة-الجدوى)
6. [سير العمل الرئيسية](#6-سير-العمل-الرئيسية)
7. [التوصيات والتطوير المستقبلي](#7-التوصيات-والتطوير-المستقبلي)

---

## 1. المتطلبات الوظيفية

### 1.1 الوحدة الأساسية (Core Module)

#### المصادقة وإدارة الحسابات

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-01 | تسجيل شركة جديدة (مالك + منظمة + اشتراك تجريبي) | عالية |
| FR-02 | تسجيل الدخول بالبريد وكلمة المرور + إصدار Token | عالية |
| FR-03 | تسجيل الخروج وإلغاء صلاحية Token | عالية |
| FR-04 | نسيت كلمة المرور (إرسال رابط بالبريد) | متوسطة |
| FR-05 | إعادة تعيين كلمة المرور | متوسطة |
| FR-06 | تجديد الـ Token | متوسطة |
| FR-07 | عرض بيانات المستخدم الحالي | عالية |

**جدول المستخدمين (users):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| name | varchar | اسم المستخدم |
| email | varchar | البريد الإلكتروني (فريد) |
| email_verified_at | timestamp | تاريخ تأكيد البريد |
| password | varchar | كلمة المرور المشفرة |
| remember_token | varchar | رمز التذكر |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### الأدوار والصلاحيات (RBAC)

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-08 | إنشاء دور جديد مع اسم ووصف | عالية |
| FR-09 | تعديل اسم ووصف الدور | عالية |
| FR-10 | حذف الدور (إذا لم يكن نظامياً) | عالية |
| FR-11 | تعيين صلاحيات للدور | عالية |
| FR-12 | تعيين دور للمستخدم | عالية |

**جدول الأدوار (roles):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| team_foreign_key | bigint | معرف المنظمة (للعزل بين المستأجرين) |
| name | varchar | اسم الدور |
| guard_name | varchar | نوع الحارس (api, web) |
| label | varchar | التسمية المعروضة |
| description | text | وصف الدور |
| is_system | boolean | هل هو دور نظامي لا يُحذف |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**أمثلة على الصلاحيات:**
- `employees:read` - قراءة بيانات الموظفين
- `employees:write` - إنشاء وتعديل الموظفين
- `invoices:read` - عرض الفواتير
- `invoices:write` - إنشاء وتعديل الفواتير
- `invoices:approve` - اعتماد الفواتير
- `invoices:cancel` - إلغاء الفواتير
- `payroll:read` - عرض الرواتب
- `payroll:approve` - اعتماد الرواتب
- `stock:read` - عرض المخزون
- `stock:adjust` - تعديل المخزون
- `workflows:read` - عرض سير العمل
- `audit:read` - عرض سجل النشاطات

---

#### العملات

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-13 | عرض جميع العملات المتاحة | متوسطة |
| FR-14 | إرجاع العملة الأساسية للمنظمة | متوسطة |
| FR-15 | حساب تحويل العملات | متوسطة |
| FR-16 | إضافة عملة جديدة (مدير) | منخفضة |
| FR-17 | تحديث سعر الصرف (مدير) | منخفضة |
| FR-18 | تعيين العملة الأساسية (مدير) | منخفضة |

**جدول العملات (currencies):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| code | varchar(3) | رمز العملة (SAR, USD, EUR) |
| name | varchar | اسم العملة |
| name_ar | varchar | الاسم بالعربية |
| symbol | varchar(10) | رمز العملة ($, ﷼) |
| decimal_places | int | عدد الخانات العشرية |
| exchange_rate | decimal(12,6) | سعر الصرف |
| is_base | boolean | هل هي العملة الأساسية |
| is_active | boolean | هل العملة نشطة |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### سجل النشاطات (Audit Log)

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-19 | عرض سجل النشاطات مع فلترة | عالية |
| FR-20 | إحصائيات النشاطات حسب النوع والتاريخ | متوسطة |
| FR-21 | عرض العمليات الحساسة فقط | عالية |
| FR-22 | عرض نشاطات مستخدم معين | متوسطة |
| FR-23 | عرض تاريخ التغييرات على كيان معين | متوسطة |

**جدول سجل النشاطات (activity_logs):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| actor_type | varchar | نوع المنفذ (User, System) |
| actor_id | bigint | معرف المنفذ |
| action | varchar | نوع العملية (created, updated, deleted) |
| subject_type | varchar | نوع الكيان المتأثر |
| subject_id | bigint | معرف الكيان المتأثر |
| old_values | json | القيم القديمة قبل التعديل |
| new_values | json | القيم الجديدة بعد التعديل |
| ip_address | varchar(45) | عنوان IP |
| user_agent | text | معلومات المتصفح |
| is_sensitive | boolean | هل العملية حساسة |
| created_at | timestamp | تاريخ العملية |

---

### 1.2 وحدة المنظمات (Organization Module)

#### المنظمات

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-24 | عرض المنظمات التابعة للمستخدم | عالية |
| FR-25 | إنشاء منظمة جديدة | عالية |
| FR-26 | تعديل بيانات المنظمة | عالية |
| FR-27 | حذف ناعم للمنظمة | عالية |
| FR-28 | تغيير حالة المنظمة (active/suspended/inactive) | عالية |
| FR-29 | إنشاء منظمات بالجملة | متوسطة |

**جدول المنظمات (organizations):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| user_id | bigint | معرف المالك |
| name | varchar | اسم المنظمة التجاري |
| legal_name | varchar | الاسم القانوني الرسمي |
| tax_number | varchar(50) | الرقم الضريبي (فريد) |
| base_currency_id | bigint | معرف العملة الأساسية |
| timezone | varchar(50) | المنطقة الزمنية |
| locale | varchar(10) | اللغة الافتراضية |
| status | enum | الحالة (active, suspended, inactive) |
| address | text | العنوان الكامل |
| phone | varchar(20) | رقم الهاتف |
| email | varchar | البريد الإلكتروني |
| website | varchar | الموقع الإلكتروني |
| logo_path | varchar | مسار الشعار |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف الناعم |

---

#### الفروع

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| name | varchar | اسم الفرع |
| code | varchar | رمز الفرع |
| address | text | عنوان الفرع |
| phone | varchar | رقم الهاتف |
| is_active | boolean | هل الفرع نشط |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### الأطراف (العملاء والموردين)

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-35 | عرض الأطراف مع فلترة | عالية |
| FR-36 | بحث بالاسم أو الرقم الضريبي | عالية |
| FR-37 | إنشاء طرف جديد (عميل/مورد) | عالية |
| FR-38 | تعديل بيانات الطرف | عالية |
| FR-39 | حذف ناعم للطرف | عالية |
| FR-40 | كشف حساب الطرف | عالية |
| FR-41 | إحصائيات المبيعات/المشتريات لكل طرف | متوسطة |
| FR-42 | تصدير بيانات الأطراف | متوسطة |

**جدول الأطراف (parties):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| name | varchar | اسم الطرف |
| name_ar | varchar | الاسم بالعربية |
| type | json | الأنواع (customer, supplier, agent, contractor) |
| email | varchar | البريد الإلكتروني |
| phone | varchar | رقم الهاتف |
| mobile | varchar | رقم الجوال |
| tax_number | varchar | الرقم الضريبي |
| address | text | العنوان |
| city | varchar | المدينة |
| country | varchar | الدولة |
| credit_limit | decimal(15,2) | حد الائتمان |
| currency_id | bigint | العملة الافتراضية |
| is_active | boolean | هل الطرف نشط |
| notes | text | ملاحظات |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف الناعم |

---

### 1.3 وحدة الاشتراكات (Subscription Module)

**جدول الخطط (plans):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| name | varchar | اسم الخطة |
| description | text | وصف الخطة |
| price | decimal(10,2) | السعر |
| billing_cycle | enum | دورة الفوترة (monthly, yearly, lifetime) |
| max_users | int | الحد الأقصى للمستخدمين |
| max_branches | int | الحد الأقصى للفروع |
| features | json | الميزات المتاحة |
| is_active | boolean | هل الخطة نشطة |
| is_popular | boolean | هل الخطة مميزة |
| sort_order | int | ترتيب العرض |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

**جدول الاشتراكات (subscriptions):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| user_id | bigint | معرف المستخدم (المالك) |
| plan_id | bigint | معرف الخطة |
| start_date | date | تاريخ البداية |
| end_date | date | تاريخ الانتهاء |
| trial_ends_at | date | تاريخ انتهاء الفترة التجريبية |
| status | enum | الحالة (active, expired, cancelled, trial) |
| auto_renew | boolean | تجديد تلقائي |
| price_paid | decimal(10,2) | المبلغ المدفوع |
| billing_cycle | varchar | دورة الفوترة |
| cancelled_at | timestamp | تاريخ الإلغاء |
| cancellation_reason | text | سبب الإلغاء |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

### 1.4 وحدة المحاسبة (Accounting Module)

#### دليل الحسابات

**جدول الحسابات (accounts):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| parent_id | bigint | معرف الحساب الأب |
| code | varchar(50) | رمز الحساب |
| name | varchar | اسم الحساب |
| name_ar | varchar | الاسم بالعربية |
| type | enum | النوع (asset, liability, equity, revenue, expense) |
| nature | enum | الطبيعة (debit, credit) |
| currency_id | bigint | العملة |
| is_active | boolean | هل الحساب نشط |
| is_system | boolean | هل حساب نظامي |
| level | int | مستوى الحساب في الشجرة |
| opening_balance | decimal(15,2) | الرصيد الافتتاحي |
| current_balance | decimal(15,2) | الرصيد الحالي |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### مراكز التكلفة

**جدول مراكز التكلفة (cost_centers):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| parent_id | bigint | معرف المركز الأب |
| code | varchar(50) | رمز المركز (فريد) |
| name | varchar | اسم المركز |
| description | text | الوصف |
| type | enum | النوع (branch, department, project, other) |
| is_active | boolean | هل المركز نشط |
| level | int | المستوى في الشجرة |
| budget | decimal(15,2) | الميزانية المخصصة |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### معدلات الضريبة

**جدول معدلات الضريبة (tax_rates):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| code | varchar(50) | رمز الضريبة (VAT15, WHT5) |
| name | varchar | اسم الضريبة |
| rate | decimal(8,4) | نسبة الضريبة |
| type | enum | النوع (sales, purchase, withholding, exempt, zero_rated) |
| calculation | enum | طريقة الحساب (percentage, fixed) |
| sales_account_id | bigint | حساب ضريبة المبيعات |
| purchase_account_id | bigint | حساب ضريبة المشتريات |
| is_compound | boolean | ضريبة مركبة |
| is_inclusive | boolean | ضريبة شاملة في السعر |
| is_default | boolean | الضريبة الافتراضية |
| is_active | boolean | هل نشطة |
| effective_from | date | تاريخ بداية السريان |
| effective_to | date | تاريخ نهاية السريان |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### الفواتير

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-77 | عرض الفواتير مع فلترة | عالية |
| FR-78 | بحث متقدم | عالية |
| FR-79 | إحصائيات المبيعات والمشتريات | عالية |
| FR-80 | إنشاء فاتورة | عالية |
| FR-81 | تعديل فاتورة (قبل الاعتماد) | عالية |
| FR-82 | حذف ناعم | عالية |
| FR-83 | اعتماد فاتورة (مسودة → معتمدة) | عالية |
| FR-84 | إلغاء فاتورة معتمدة | عالية |
| FR-85 | نسخ فاتورة | متوسطة |
| FR-86 | تصدير PDF | متوسطة |
| FR-87 | تصدير Excel/CSV | متوسطة |

**جدول الفواتير (invoices):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| branch_id | bigint | معرف الفرع |
| cost_center_id | bigint | مركز التكلفة |
| party_id | bigint | معرف الطرف |
| number | varchar(50) | رقم الفاتورة (فريد) |
| type | enum | النوع (sales, purchase, sales_return, purchase_return) |
| status | enum | الحالة (draft, pending, approved, paid, cancelled) |
| date | date | تاريخ الفاتورة |
| due_date | date | تاريخ الاستحقاق |
| currency_code | varchar(3) | رمز العملة |
| exchange_rate | decimal(12,6) | سعر الصرف |
| subtotal | decimal(15,2) | المجموع الفرعي |
| discount_amount | decimal(15,2) | مبلغ الخصم |
| tax_amount | decimal(15,2) | مبلغ الضريبة |
| total | decimal(15,2) | الإجمالي |
| paid_amount | decimal(15,2) | المبلغ المدفوع |
| balance | decimal(15,2) | المتبقي |
| notes | text | ملاحظات |
| created_by | bigint | أنشأها |
| approved_by | bigint | اعتمدها |
| approved_at | timestamp | تاريخ الاعتماد |
| cancelled_at | timestamp | تاريخ الإلغاء |
| cancellation_reason | text | سبب الإلغاء |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### المدفوعات

**جدول المدفوعات (payments):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| invoice_id | bigint | معرف الفاتورة |
| cost_center_id | bigint | مركز التكلفة |
| party_id | bigint | معرف الطرف |
| number | varchar(50) | رقم الإيصال |
| type | enum | النوع (receipt, payment) |
| method | enum | طريقة الدفع (cash, bank, cheque, card) |
| amount | decimal(15,2) | المبلغ |
| currency_code | varchar(3) | العملة |
| exchange_rate | decimal(12,6) | سعر الصرف |
| date | date | تاريخ الدفع |
| reference | varchar | المرجع |
| cheque_number | varchar | رقم الشيك |
| cheque_date | date | تاريخ الشيك |
| notes | text | ملاحظات |
| created_by | bigint | أنشأها |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### القيود اليومية

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-95 | عرض القيود | عالية |
| FR-96 | إنشاء قيد يومي | عالية |
| FR-97 | تعديل قيد (قبل الترحيل) | عالية |
| FR-98 | حذف قيد مسودة | عالية |
| FR-99 | ترحيل القيد | عالية |
| FR-100 | إلغاء قيد مرحل | عالية |
| FR-101 | عكس القيد | متوسطة |
| FR-102 | ميزان المراجعة | عالية |
| FR-103 | كشف حساب | عالية |

**جدول دفعات القيود (journal_batches):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| branch_id | bigint | معرف الفرع |
| number | varchar(50) | رقم القيد (فريد) |
| date | date | تاريخ القيد |
| description | text | الوصف |
| type | enum | النوع (manual, auto, adjustment, closing, opening, reversal) |
| status | enum | الحالة (draft, pending, posted, voided) |
| source_type | varchar | مصدر القيد (Invoice, Payment) |
| source_id | bigint | معرف المصدر |
| currency_code | varchar(3) | العملة |
| total_debit | decimal(15,2) | إجمالي المدين |
| total_credit | decimal(15,2) | إجمالي الدائن |
| created_by | bigint | أنشأه |
| posted_by | bigint | رحله |
| posted_at | timestamp | تاريخ الترحيل |
| voided_at | timestamp | تاريخ الإلغاء |
| void_reason | text | سبب الإلغاء |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

**جدول بنود القيد (journal_lines):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| batch_id | bigint | معرف القيد |
| account_id | bigint | معرف الحساب |
| cost_center_id | bigint | مركز التكلفة |
| line_number | int | رقم السطر |
| description | text | الوصف |
| debit | decimal(15,2) | المدين |
| credit | decimal(15,2) | الدائن |
| debit_fc | decimal(15,2) | المدين بالعملة الأجنبية |
| credit_fc | decimal(15,2) | الدائن بالعملة الأجنبية |
| currency_code | varchar(3) | العملة |
| exchange_rate | decimal(12,6) | سعر الصرف |
| party_id | bigint | معرف الطرف |
| tax_rate_id | bigint | معرف الضريبة |
| tax_amount | decimal(15,2) | مبلغ الضريبة |
| due_date | date | تاريخ الاستحقاق |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

### 1.5 وحدة المخزون (Inventory Module)

#### المنتجات

| رقم | المتطلب | الأولوية |
|-----|---------|---------|
| FR-104 | عرض المنتجات مع فلترة | عالية |
| FR-105 | البحث بالاسم أو SKU | عالية |
| FR-106 | إحصائيات المنتجات والقيمة الإجمالية | عالية |
| FR-107 | قائمة المنتجات تحت الحد الأدنى | عالية |
| FR-108 | إنشاء منتج جديد | عالية |
| FR-109 | تعديل بيانات المنتج | عالية |
| FR-110 | حذف ناعم | عالية |
| FR-111 | تصدير Excel/CSV | متوسطة |
| FR-112 | استيراد من Excel/CSV | متوسطة |

**جدول المنتجات (products):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| category_id | bigint | معرف التصنيف |
| unit_id | bigint | معرف الوحدة |
| sku | varchar(100) | رمز المنتج (فريد) |
| barcode | varchar(100) | الباركود |
| name | varchar | اسم المنتج |
| name_ar | varchar | الاسم بالعربية |
| description | text | الوصف |
| type | enum | النوع (product, service, consumable) |
| tracking_type | enum | نوع التتبع (none, serial, batch) |
| has_expiry | boolean | له تاريخ انتهاء |
| unit_price | decimal(15,4) | سعر البيع |
| cost_price | decimal(15,4) | سعر التكلفة |
| tax_rate_id | bigint | معرف الضريبة الافتراضية |
| min_stock | decimal(15,4) | الحد الأدنى للمخزون |
| reorder_point | decimal(15,4) | نقطة إعادة الطلب |
| reorder_quantity | decimal(15,4) | كمية إعادة الطلب |
| sales_account_id | bigint | حساب المبيعات |
| purchase_account_id | bigint | حساب المشتريات |
| inventory_account_id | bigint | حساب المخزون |
| is_active | boolean | هل المنتج نشط |
| is_sellable | boolean | قابل للبيع |
| is_purchasable | boolean | قابل للشراء |
| weight | decimal(10,4) | الوزن |
| dimensions | json | الأبعاد |
| image_path | varchar | صورة المنتج |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### المستودعات

**جدول المستودعات (warehouses):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| branch_id | bigint | معرف الفرع |
| code | varchar(50) | رمز المستودع |
| name | varchar | اسم المستودع |
| address | text | العنوان |
| manager_id | bigint | معرف مدير المستودع |
| is_active | boolean | هل المستودع نشط |
| is_default | boolean | المستودع الافتراضي |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### الأرقام التسلسلية والدفعات

**جدول الأرقام التسلسلية (serial_numbers):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| product_id | bigint | معرف المنتج |
| warehouse_id | bigint | معرف المستودع |
| serial_number | varchar | الرقم التسلسلي (فريد) |
| status | enum | الحالة (available, reserved, sold, returned, damaged, expired) |
| purchase_invoice_id | bigint | فاتورة الشراء |
| sales_invoice_id | bigint | فاتورة البيع |
| warranty_expiry | date | تاريخ انتهاء الضمان |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

**جدول الدفعات (batches):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| product_id | bigint | معرف المنتج |
| warehouse_id | bigint | معرف المستودع |
| batch_number | varchar | رقم الدفعة |
| quantity | decimal(15,4) | الكمية |
| reserved_quantity | decimal(15,4) | الكمية المحجوزة |
| manufacturing_date | date | تاريخ التصنيع |
| expiry_date | date | تاريخ الانتهاء |
| cost_per_unit | decimal(15,4) | تكلفة الوحدة |
| status | enum | الحالة (active, expired, depleted, recalled) |
| purchase_invoice_id | bigint | فاتورة الشراء |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### حركات المخزون

**جدول حركات المخزون (stock_movements):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| warehouse_id | bigint | معرف المستودع |
| product_id | bigint | معرف المنتج |
| batch_id | bigint | معرف الدفعة |
| serial_number_id | bigint | معرف الرقم التسلسلي |
| type | enum | النوع (purchase, sale, transfer_in, transfer_out, adjustment, return) |
| quantity | decimal(15,4) | الكمية |
| unit_cost | decimal(15,4) | تكلفة الوحدة |
| total_cost | decimal(15,4) | التكلفة الإجمالية |
| balance_before | decimal(15,4) | الرصيد قبل |
| balance_after | decimal(15,4) | الرصيد بعد |
| reference_type | varchar | نوع المرجع |
| reference_id | bigint | معرف المرجع |
| notes | text | ملاحظات |
| created_by | bigint | أنشأها |
| created_at | timestamp | تاريخ الحركة |

**جدول أرصدة المخزون (stock_balances) - جدول محسوب مسبقاً:**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| warehouse_id | bigint | معرف المستودع |
| product_id | bigint | معرف المنتج |
| quantity | decimal(15,4) | الكمية الفعلية |
| reserved_quantity | decimal(15,4) | الكمية المحجوزة |
| available_quantity | decimal(15,4) | الكمية المتاحة |
| average_cost | decimal(15,4) | متوسط التكلفة |
| total_value | decimal(15,4) | القيمة الإجمالية |
| last_movement_at | timestamp | تاريخ آخر حركة |

---

#### تحويلات المخزون

**جدول تحويلات المخزون (stock_transfers):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| number | varchar(50) | رقم التحويل |
| from_warehouse_id | bigint | مستودع الإرسال |
| to_warehouse_id | bigint | مستودع الاستلام |
| status | enum | الحالة (draft, pending, approved, shipped, received, cancelled) |
| requested_by | bigint | طلبه |
| approved_by | bigint | اعتمده |
| shipped_by | bigint | شحنه |
| received_by | bigint | استلمه |
| notes | text | ملاحظات |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

### 1.6 وحدة الموارد البشرية (HR Module)

#### الموظفون

**جدول الموظفين (employees):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| user_id | bigint | حساب المستخدم |
| branch_id | bigint | معرف الفرع |
| department_id | bigint | معرف القسم |
| position_id | bigint | معرف المنصب |
| manager_id | bigint | معرف المدير المباشر |
| employee_number | varchar(50) | رقم الموظف |
| first_name | varchar | الاسم الأول |
| last_name | varchar | الاسم الأخير |
| national_id | varchar(50) | رقم الهوية |
| nationality | varchar(5) | الجنسية |
| date_of_birth | date | تاريخ الميلاد |
| gender | enum | الجنس (male, female) |
| marital_status | enum | الحالة الاجتماعية |
| hire_date | date | تاريخ الالتحاق |
| probation_end_date | date | نهاية التجربة |
| contract_type | enum | نوع العقد (permanent, contract, part_time) |
| basic_salary | decimal(15,2) | الراتب الأساسي |
| status | enum | الحالة (active, on_leave, terminated, suspended) |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

---

#### الحضور والانصراف

**جدول الحضور (attendances):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| employee_id | bigint | معرف الموظف |
| date | date | التاريخ |
| check_in | datetime | وقت الحضور |
| check_out | datetime | وقت الانصراف |
| check_in_lat | decimal(10,7) | خط عرض GPS للحضور |
| check_in_lng | decimal(10,7) | خط طول GPS للحضور |
| check_out_lat | decimal(10,7) | خط عرض GPS للانصراف |
| check_out_lng | decimal(10,7) | خط طول GPS للانصراف |
| check_in_ip | varchar(45) | IP عند الحضور |
| check_out_ip | varchar(45) | IP عند الانصراف |
| status | enum | الحالة (present, absent, late, half_day, on_leave) |
| working_hours | decimal(5,2) | ساعات العمل |
| overtime_hours | decimal(5,2) | ساعات العمل الإضافي |
| notes | text | ملاحظات |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### الرواتب

**جدول مكونات الراتب (salary_components):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| name | varchar | اسم المكون |
| type | enum | النوع (earning, deduction, benefit) |
| calculation | enum | الحساب (fixed, percentage, formula) |
| value | decimal(15,2) | القيمة |
| is_taxable | boolean | خاضع للضريبة |
| is_active | boolean | هل نشط |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**جدول مسيرات الرواتب (payroll_runs):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| year | int | السنة |
| month | int | الشهر |
| status | enum | الحالة (draft, processing, approved, paid, cancelled) |
| total_gross | decimal(15,2) | إجمالي الرواتب |
| total_deductions | decimal(15,2) | إجمالي الاستقطاعات |
| total_net | decimal(15,2) | إجمالي الصافي |
| processed_by | bigint | معالجه |
| approved_by | bigint | معتمده |
| approved_at | timestamp | تاريخ الاعتماد |
| paid_at | timestamp | تاريخ الصرف |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**جدول قسائم الرواتب (payslips):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| payroll_run_id | bigint | معرف مسير الرواتب |
| employee_id | bigint | معرف الموظف |
| basic_salary | decimal(15,2) | الراتب الأساسي |
| gross_salary | decimal(15,2) | الراتب الإجمالي |
| total_earnings | decimal(15,2) | إجمالي البدلات |
| total_deductions | decimal(15,2) | إجمالي الاستقطاعات |
| net_salary | decimal(15,2) | الراتب الصافي |
| working_days | int | أيام العمل |
| absent_days | int | أيام الغياب |
| overtime_hours | decimal(8,2) | ساعات الإضافي |
| status | enum | الحالة (draft, approved, paid, cancelled) |
| paid_at | timestamp | تاريخ الصرف |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

#### القروض

**جدول قروض الموظفين (employee_loans):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| employee_id | bigint | معرف الموظف |
| amount | decimal(15,2) | مبلغ القرض |
| installments | int | عدد الأقساط |
| monthly_installment | decimal(15,2) | قيمة القسط الشهري |
| remaining_amount | decimal(15,2) | المبلغ المتبقي |
| start_date | date | تاريخ البداية |
| status | enum | الحالة (pending, approved, active, completed, rejected) |
| approved_by | bigint | اعتمده |
| approved_at | timestamp | تاريخ الاعتماد |
| notes | text | ملاحظات |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

### 1.7 وحدة المشاريع (Projects Module)

**جدول المشاريع (projects):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| name | varchar | اسم المشروع |
| description | text | الوصف |
| status | enum | الحالة (planning, active, on_hold, completed, cancelled) |
| priority | enum | الأولوية (low, medium, high, critical) |
| start_date | date | تاريخ البداية |
| end_date | date | تاريخ الانتهاء |
| budget | decimal(15,2) | الميزانية |
| progress | int | نسبة الإنجاز |
| manager_id | bigint | مدير المشروع |
| party_id | bigint | العميل |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |
| deleted_at | timestamp | تاريخ الحذف |

**جدول المهام (tasks):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| project_id | bigint | معرف المشروع |
| parent_id | bigint | معرف المهمة الأب |
| title | varchar | عنوان المهمة |
| description | text | الوصف |
| status | enum | الحالة (todo, in_progress, review, done, cancelled) |
| priority | enum | الأولوية |
| assigned_to | bigint | معين لـ |
| due_date | date | تاريخ الاستحقاق |
| estimated_hours | decimal(8,2) | الساعات التقديرية |
| actual_hours | decimal(8,2) | الساعات الفعلية |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

### 1.8 محرك سير العمل (Workflow Engine)

**جدول تعريفات سير العمل (workflows):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| organization_id | bigint | معرف المنظمة |
| name | varchar | اسم سير العمل |
| entity_type | varchar | نوع الكيان (Invoice, StockTransfer, PayrollRun) |
| trigger_on | enum | يُشغَّل عند (submit, create, amount_exceeds) |
| conditions | json | شروط التشغيل |
| is_active | boolean | هل نشط |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**جدول خطوات سير العمل (workflow_steps):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| workflow_id | bigint | معرف سير العمل |
| order | int | ترتيب الخطوة |
| name | varchar | اسم الخطوة |
| approver_type | enum | نوع المعتمد (user, role, manager, department_head) |
| approver_id | bigint | معرف المعتمد |
| on_approve | enum | عند الموافقة (next_step, complete) |
| on_reject | enum | عند الرفض (reject_all, previous_step) |
| on_timeout | enum | عند انتهاء الوقت (auto_approve, auto_reject, escalate) |
| timeout_hours | int | المهلة بالساعات |
| conditions | json | شروط تخطي الخطوة |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**جدول نسخ سير العمل (workflow_instances):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| workflow_id | bigint | معرف سير العمل |
| entity_type | varchar | نوع الكيان |
| entity_id | bigint | معرف الكيان |
| current_step | int | الخطوة الحالية |
| status | enum | الحالة (pending, approved, rejected, cancelled) |
| initiated_by | bigint | بدأه |
| completed_at | timestamp | تاريخ الاكتمال |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**جدول الموافقات (workflow_approvals):**

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint | المعرف الفريد |
| instance_id | bigint | معرف النسخة |
| step_id | bigint | معرف الخطوة |
| approver_id | bigint | معرف المعتمد |
| action | enum | الإجراء (approved, rejected, delegated) |
| comments | text | التعليق |
| delegated_to | bigint | مفوض إلى |
| acted_at | timestamp | تاريخ الإجراء |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

## 2. المتطلبات غير الوظيفية

### 2.1 الأمان

| المتطلب | التفاصيل |
|---------|---------|
| **المصادقة** | Laravel Sanctum - Bearer Token لكل طلب API |
| **التفويض** | Spatie RBAC مع عزل الصلاحيات حسب المنظمة |
| **عزل البيانات** | كل استعلام يُقيَّد بـ organization_id |
| **تشفير البيانات** | bcrypt لكلمات المرور، HTTPS إلزامي |
| **سجل المراجعة** | تسجيل جميع العمليات مع IP وUser Agent |
| **الحذف الناعم** | البيانات لا تُحذف نهائياً لضمان التتبع |

### 2.2 الأداء

| المتطلب | التفاصيل |
|---------|---------|
| **الفهارس** | فهارس مركبة على (organization_id + status) |
| **جداول محسوبة** | stock_balances لتسريع استعلامات المخزون |
| **Pagination** | ترقيم الصفحات على جميع القوائم |
| **التخزين المؤقت** | Cache للبيانات الثابتة (العملات، الإعدادات) |
| **المعالجة الخلفية** | Queue Jobs للعمليات الثقيلة (التصدير، الإشعارات) |

### 2.3 القابلية للتوسع

| المتطلب | التفاصيل |
|---------|---------|
| **حدود الخطط** | تحكم في max_users وmax_branches لكل خطة |
| **بنية معيارية** | وحدات منفصلة قابلة للتعطيل حسب الخطة |
| **API موحد** | RESTful API كامل لدعم تطبيقات الجوال |
| **متعدد العملات** | دعم عملات متعددة مع سعر صرف ديناميكي |

### 2.4 الموثوقية

| المتطلب | التفاصيل |
|---------|---------|
| **المعاملات** | Database Transactions للعمليات المتعددة |
| **التحقق من البيانات** | Form Requests لكل endpoint |
| **معالجة الأخطاء** | استجابات API موحدة مع رموز خطأ واضحة |
| **النسخ الاحتياطي** | Soft Deletes لحماية البيانات |

---

## 3. مخطط حالات الاستخدام

```
┌─────────────────────────────────────────────────────────┐
│                 نظام ERP السحابي                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  [المدير العام] ─┬─ إدارة الخطط والاشتراكات             │
│                  └─ إدارة العملات والإعدادات العامة      │
│                                                         │
│  [مالك المنظمة] ─┬─ تسجيل الشركة                       │
│                  ├─ إدارة الفروع والمستخدمين             │
│                  └─ الاشتراك في خطة                     │
│                                                         │
│  [المحاسب] ──────┬─ إدارة الفواتير والمدفوعات           │
│                  ├─ القيود اليومية والترحيل              │
│                  └─ التقارير المالية                     │
│                                                         │
│  [مدير المخزون] ─┬─ إدارة المنتجات والمستودعات          │
│                  ├─ حركات وتحويلات المخزون               │
│                  └─ تتبع الأرقام التسلسلية والدفعات      │
│                                                         │
│  [مدير HR] ──────┬─ إدارة الموظفين والأقسام             │
│                  ├─ معالجة مسير الرواتب                  │
│                  └─ اعتماد الإجازات والقروض              │
│                                                         │
│  [الموظف] ───────┬─ تسجيل الحضور/الانصراف              │
│                  ├─ طلب إجازة أو قرض                    │
│                  └─ عرض قسيمة الراتب                    │
│                                                         │
│  [مدير المشاريع] ┬─ إنشاء وإدارة المشاريع               │
│                  └─ توزيع المهام وتتبع الإنجاز           │
│                                                         │
│  [المعتمد] ──────┬─ عرض الطلبات المعلقة                 │
│                  └─ موافقة / رفض / تفويض                │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 4. مخطط الفئات

```
┌─────────────────── البنية الأساسية ──────────────────────┐

User ──────────────── Organization ──────── Branch
  │                       │
  │                       ├── Party (Customer/Supplier)
  │                       ├── Account (Chart of Accounts)
  │                       ├── Product ── Warehouse
  │                       ├── Employee
  │                       └── Project
  │
  └── Subscription ─────── Plan

┌─────────────────── المحاسبة ─────────────────────────────┐

Invoice ─────────── InvoiceLine ──────── TaxRate
   │                                        │
   └── Payment                         CostCenter
   │
JournalBatch ────── JournalLine ──────── Account

┌─────────────────── المخزون ──────────────────────────────┐

Product ──────────── StockBalance (Cached)
   │                      │
   ├── SerialNumber    Warehouse
   └── Batch
         │
    StockMovement ─── StockTransfer ─── StockTransferLine

┌─────────────────── الموارد البشرية ──────────────────────┐

Employee ──────────── Attendance
   │                       │
   ├── EmployeeLoan    AttendanceSettings
   │
   └── Payslip ────── PayslipItem
         │
    PayrollRun ──────── SalaryComponent

┌─────────────────── سير العمل ────────────────────────────┐

Workflow ─────────── WorkflowStep
   │
WorkflowInstance ─── WorkflowApproval
```

---

## 5. دراسة الجدوى

### 5.1 الجدوى التقنية

| العنصر | التقييم | الملاحظات |
|--------|---------|-----------|
| **Laravel 11** | ✅ ممتازة | إطار حديث مع دعم طويل الأمد |
| **البنية المعمارية** | ✅ متينة | Repository + Services + Resources |
| **قاعدة البيانات** | ✅ منظمة | فهارس وعلاقات صحيحة |
| **تعدد المستأجرين** | ✅ مطبق | عزل كامل بـ organization_id |
| **الأمان** | ✅ قوي | Sanctum + RBAC + Soft Deletes |
| **التوثيق** | ⚠️ جزئي | Swagger موجود يحتاج استكمال |
| **الاختبارات** | ⚠️ ناقص | البنية موجودة لكن تحتاج تطوير |

### 5.2 الجدوى التشغيلية

| العنصر | التقييم |
|--------|---------|
| **النشر** | ✅ قياسي على أي استضافة Laravel |
| **الصيانة** | ✅ كود نظيف سهل الصيانة |
| **التدريب** | ✅ واجهة API موحدة |
| **التكامل** | ✅ RESTful API كامل |

### 5.3 الجدوى الاقتصادية

| البند | التقدير |
|-------|---------|
| **تكلفة التطوير** | متوسطة - الأساس القوي يوفر 60% من العمل |
| **تكلفة البنية التحتية** | منخفضة-متوسطة ($50-$500/شهر حسب الحجم) |
| **تكلفة الصيانة** | منخفضة - كود منظم |
| **العائد المتوقع** | عالي - سوق ERP السحابي ينمو 10% سنوياً |

### 5.4 نسبة إنجاز الوحدات

| الوحدة | نسبة الإنجاز | الملاحظات |
|--------|-------------|-----------|
| النظام الأساسي | ✅ 90% | يحتاج استكمال User Management |
| المنظمات | ✅ 95% | شبه مكتملة |
| المحاسبة | ✅ 85% | التقارير تحتاج تطوير |
| المخزون | ✅ 85% | بعض العمليات تحتاج اكتمال |
| الموارد البشرية | ✅ 80% | تقارير الرواتب تحتاج عمل |
| المشاريع | ⚠️ 60% | وحدة تسجيل الوقت ناقصة |
| سير العمل | ✅ 85% | محرك قوي يحتاج اختباراً |
| الاشتراكات | ✅ 90% | بوابة الدفع مفقودة |
| التقارير | ❌ 20% | معظمها TODO |

---

## 6. سير العمل الرئيسية

### 6.1 تسجيل شركة جديدة
```
المستخدم يملأ النموذج
        ↓
إنشاء حساب المستخدم
        ↓
إنشاء المنظمة
        ↓
إنشاء اشتراك تجريبي (14 يوم)
        ↓
إرسال بريد ترحيبي
        ↓
دخول للوحة التحكم
```

### 6.2 دورة حياة الفاتورة
```
إنشاء مسودة
     ↓
تقديم للاعتماد ──── سير عمل ────→ رفض → إعادة للمسودة
     ↓                                        ↑
  اعتماد                               تعديل وإعادة
     ↓
  إرسال للعميل
     ↓
  دفع جزئي → متأخرة السداد
     ↓
  دفع كامل → مسددة
```

### 6.3 مسير الرواتب الشهري
```
إنشاء مسير الشهر
        ↓
جمع بيانات الحضور والغياب
        ↓
حساب المكونات (بدلات + استقطاعات + قروض)
        ↓
مراجعة القسائم
        ↓
اعتماد المسير
        ↓
صرف الرواتب + تقييد محاسبي
```

### 6.4 تحويل المخزون بين مستودعين
```
طلب التحويل
      ↓
تقديم للاعتماد
      ↓
موافقة المدير
      ↓
شحن من المستودع الأول (خصم الكمية)
      ↓
استلام في المستودع الثاني (إضافة الكمية)
      ↓
تحديث أرصدة المخزون تلقائياً
```

### 6.5 محرك الموافقات (Workflow Engine)
```
تقديم طلب (فاتورة، تحويل، راتب، قرض)
              ↓
التحقق من وجود سير عمل مفعّل
              ↓
إنشاء نسخة سير العمل
              ↓
    ┌─── الخطوة الأولى ───┐
    │                     │
  موافقة               رفض
    │                     │
    ↓                     ↓
الخطوة التالية     إشعار مقدم الطلب
    │
    ↓
اكتمال جميع الخطوات
    │
    ↓
تحديث حالة الكيان تلقائياً
```

---

## 7. التوصيات والتطوير المستقبلي

### 🔴 الأولوية القصوى (يجب تنفيذها فوراً)

#### 1. نظام الإشعارات الفورية (Real-Time Notifications)

| الإضافة | التفاصيل |
|---------|---------|
| **WebSockets** | Laravel Reverb أو Pusher |
| **قنوات متعددة** | داخل التطبيق + بريد + SMS + WhatsApp |
| **أنواع** | موافقة معلقة، فاتورة متأخرة، مخزون منخفض، راتب جاهز |
| **تفضيلات** | كل مستخدم يتحكم بما يتلقاه |

#### 2. التقارير المالية الكاملة

| التقرير | الوصف |
|---------|-------|
| **قائمة الدخل** | الإيرادات والمصروفات والصافي |
| **الميزانية العمومية** | الأصول والخصوم وحقوق الملكية |
| **التدفق النقدي** | حركة النقد الداخل والخارج |
| **تقرير الضريبة** | VAT والضرائب المستحقة |
| **تقرير الذمم** | المديونيات والمستحقات |
| **تقرير المخزون** | التقييم، الحركات، الراكد |
| **لوحات BI تفاعلية** | رسوم بيانية حسب الفترة |

#### 3. بوابة الدفع الإلكتروني

| البوابة | الأهمية |
|---------|---------|
| **Moyasar** | الأكثر استخداماً في السعودية |
| **Stripe** | للمدفوعات الدولية |
| **Apple Pay / STC Pay** | ضروري للسوق السعودي |
| **فواتير تلقائية** | إصدار فاتورة عند كل تجديد اشتراك |

#### 4. المصادقة الثنائية (2FA)

| الطريقة | الوصف |
|---------|-------|
| **TOTP** | Google Authenticator / Authy |
| **SMS OTP** | رمز عبر الجوال |
| **Email OTP** | رمز عبر البريد |
| **Backup Codes** | رموز احتياطية |
| **إلزامية** | للأدوار الحساسة (محاسب، مدير، مالك) |

---

### 🟠 أولوية عالية

#### 5. الفاتورة الإلكترونية ZATCA (إلزامي للسوق السعودي)

| المتطلب | الوصف |
|---------|-------|
| **ZATCA Phase 2** | UUID، QR Code، XML بمعيار UBL 2.1 |
| **التوقيع الرقمي** | شهادة رقمية معتمدة |
| **إرسال للهيئة** | API مباشر مع هيئة الزكاة |
| **QR Code** | على كل فاتورة |
| **الأرشفة** | حفظ إلزامي 6 سنوات |

#### 6. الذكاء الاصطناعي (AI Features)

| الميزة | الوصف |
|--------|-------|
| **توقع المخزون** | تنبؤ احتياجات الشراء |
| **كشف الشذوذ** | تحديد العمليات غير المعتادة |
| **تصنيف تلقائي** | تصنيف الحسابات والمصروفات |
| **مساعد AI** | إجابة عن استفسارات البيانات |
| **توقع التدفق النقدي** | تنبؤ بالوضع النقدي |

#### 7. تحسين API الجوال

| الإضافة | الوصف |
|---------|-------|
| **Push Notifications** | Firebase FCM |
| **مسح QR/Barcode** | لمنتجات المخزون والحضور |
| **بصمة / Face ID** | تسجيل دخول سريع |
| **Offline Support** | مزامنة عند عودة الاتصال |

#### 8. إدارة المستندات (DMS)

| الميزة | الوصف |
|--------|-------|
| **رفع على Cloud** | AWS S3 / Azure Blob |
| **OCR** | قراءة الفواتير الورقية تلقائياً |
| **توقيع إلكتروني** | توقيع العقود داخل النظام |
| **صلاحيات الوصول** | من يرى ماذا |

---

### 🟡 أولوية متوسطة

#### 9. التكاملات الخارجية

| التكامل | الوصف |
|---------|-------|
| **Webhook System** | إشعار الأنظمة عند أي حدث |
| **OAuth 2.0** | API عام للتكامل مع أطراف ثالثة |
| **WooCommerce/Shopify** | مزامنة المنتجات والطلبات |
| **Qiwa / GOSI** | تكامل حكومي |
| **Zapier / Make** | ربط مع آلاف التطبيقات |

#### 10. إدارة العقود

| الميزة | الوصف |
|--------|-------|
| **قوالب العقود** | عقود موظفين، موردين، عملاء |
| **تتبع التجديدات** | تنبيه قبل انتهاء العقد |
| **التوقيع الإلكتروني** | اعتماد رقمي |

#### 11. إدارة الأصول الثابتة

| الميزة | الوصف |
|--------|-------|
| **سجل الأصول** | أجهزة، مركبات، عقارات |
| **الاستهلاك التلقائي** | حساب وقيد شهري |
| **جدولة الصيانة** | تذكير بالصيانة الدورية |

#### 12. نقطة البيع (POS)

| الميزة | الوصف |
|--------|-------|
| **واجهة كاشير** | سريعة وسهلة |
| **تكامل كامل** | مع المخزون والمحاسبة |
| **دفع إلكتروني** | mada، Visa، STC Pay |

#### 13. توطين كامل للغة العربية

| الميزة | الوصف |
|--------|-------|
| **تقويم هجري** | عرض التواريخ بالهجري |
| **RTL كامل** | دعم اتجاه اليمين |
| **أرقام عربية** | خيار عرض الأرقام بالعربية |

---

### 🟢 خطة تطوير مستقبلية

#### 14. بوابة العملاء B2B Portal
```
بوابة إلكترونية للعملاء:
- عرض الكتالوج مع الأسعار الخاصة
- إنشاء أوامر الشراء
- تتبع حالة الطلب
- سجل الفواتير والمدفوعات
- تنزيل الكشوفات
```

#### 15. التخطيط المتقدم
```
- ميزانية تقديرية مقابل الفعلية
- تخطيط احتياجات المواد MRP
- نقاط إعادة الطلب الذكية
- توقع الطلب
```

---

## 📊 خطة التنفيذ المقترحة

```
المرحلة 1 - الأساسيات (3 أشهر):
├── إكمال التقارير المالية
├── تفعيل نظام الإشعارات الفورية
├── بوابة الدفع (Moyasar + Stripe)
└── المصادقة الثنائية (2FA)

المرحلة 2 - الامتثال (3 أشهر):
├── الفاتورة الإلكترونية ZATCA
├── رفع المستندات على Cloud
├── Webhooks للتكامل
└── توطين كامل (هجري + RTL)

المرحلة 3 - التوسع (6 أشهر):
├── ميزات الذكاء الاصطناعي
├── تحسين API الجوال
├── إدارة الأصول الثابتة
└── بوابة B2B للعملاء

المرحلة 4 - الابتكار (مستمر):
├── POS
├── تكاملات متقدمة
└── BI متقدم وتحليلات
```

---

## 🏆 أهم 3 إضافات للتميز في السوق

| الأولوية | الإضافة | السبب |
|----------|---------|-------|
| **#1** | **ZATCA e-Invoicing** | إلزامي قانونياً للسوق السعودي |
| **#2** | **Real-Time Notifications** | يحسن تجربة المستخدم جذرياً |
| **#3** | **AI Assistant + BI Reports** | يميز النظام عن جميع المنافسين |

---

> **ملاحظة:** هذا التحليل مبني على قراءة كاملة لملفات قاعدة البيانات، النماذج (Models)، المسارات (Routes)، والمتحكمات (Controllers) للمشروع.
