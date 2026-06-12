---
description: Create Swagger/OpenAPI schema documentation for a new or existing backend module
---

# Create Swagger Schema

Generate a complete Swagger schema file for a backend API module following the project's established design patterns.

## Project Context

- **Framework**: Laravel with `l5-swagger` (PHP OpenAPI attributes via `OpenApi\Attributes as OA`)
- **Swagger directory**: `app/Swagger/`
- **Config**: `config/l5-swagger.php` — scans `app/Http/Controllers`, `app/Http/Resources`, `app/Swagger`
- **Annotations registry**: `app/Http/Controllers/Api/SwaggerAnnotations.php`
- **Tags registry**: `app/Swagger/BaseSchemas.php` (all `#[OA\Tag]` definitions live here centrally)
- **Base response schemas**: `ApiResponse` and `MessageResponse` are defined in `BaseSchemas.php`

## Steps

### 1. Gather information about the module

Read these files to understand the module's data structure and endpoints:

- **Model**: `app/Models/{ModelName}.php` — check `$fillable`, `casts()`, relationships, and scopes
- **Controller**: `app/Http/Controllers/Api/{ModelName}Controller.php` — check all public methods (endpoints)
- **Routes**: `routes/api/*.php` — find the route file that registers this controller's routes, note the prefix and middleware
- **Store/Update Requests**: `app/Http/Requests/{ModelName}/Store{ModelName}Request.php` and `Update{ModelName}Request.php` — check validation rules
- **Migration**: `database/migrations/*_create_{table_name}_table.php` — check column types, nullable, defaults, enums

If the model has empty `$fillable`, use the migration to determine fields.

### 2. Create the schema file

Create `app/Swagger/{ModelName}Schemas.php` following this exact structure:

```php
<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

// --- SECTION 1: Data schemas (model, store request, update request, response wrappers) ---

#[OA\Schema(
    schema: "{ModelName}",
    properties: [
        // All model fields with types, examples, formats, enums, nullable as appropriate
        // Always include: id, timestamps (created_at, updated_at)
        // Use proper types: "integer", "string", "number", "boolean"
        // Use format for: "date-time", "date", "email", "float"
        // Use enum for constrained values: enum: ["value1", "value2"]
        // Mark nullable fields with: nullable: true
    ]
)]
#[OA\Schema(
    schema: "{ModelName}StoreRequest",
    required: ["field1", "field2"],  // Only truly required fields
    properties: [
        // Writable fields only (exclude id, timestamps, auto-set fields like organization_id, created_by)
        // Optional fields should have: nullable: true
    ]
)]
#[OA\Schema(
    schema: "{ModelName}UpdateRequest",
    properties: [
        // Same as StoreRequest but without required constraint (partial updates)
    ]
)]
#[OA\Schema(
    schema: "{ModelName}Response",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/{ModelName}"),
    ]
)]
#[OA\Schema(
    schema: "{ModelName}ListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/{ModelName}")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]

// --- SECTION 2: Endpoint annotations ---
// One #[OA\Get/Post/Put/Delete] per controller method
// Order: index, store, then custom actions (statistics, search, export, bulk ops), then show/update/destroy by {id}, then sub-actions on {id}

#[OA\Get(
    path: "/{route-prefix}/{resources}",
    summary: "List {resources}",
    description: "Returns paginated list of {resources}",
    security: [["sanctum" => []]],
    tags: ["{Tag Name}"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/{ModelName}ListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
// ... POST (store), GET (show), PUT (update), DELETE (destroy), plus any custom endpoints

class {ModelName}Schemas
{
    // {ModelName} schemas and endpoint documentation.
}
```

### 3. Endpoint annotation rules

| Controller method | HTTP | Response code | Notes |
|---|---|---|---|
| `index` | GET | 200 | Use `{ModelName}ListResponse`, add `per_page` query param |
| `store` | POST | 201 | Use `{ModelName}StoreRequest` body, return `{ModelName}Response` |
| `show` | GET | 200 | Path param `{id}`, return `{ModelName}Response`, add 404 |
| `update` | PUT | 200 | Path param `{id}`, use `{ModelName}UpdateRequest` body, add 404+422 |
| `destroy` | DELETE | 204 | Path param `{id}`, add 404 |
| `search` | GET | 200 | Add all search criteria as query params from controller's `request()->only([...])` |
| `statistics` | GET | 200 | Return `MessageResponse` |
| `export` | GET | 200/501 | Add 501 if not implemented |
| `import` | POST | 200 | Use `multipart/form-data` with `file` binary property |
| Bulk operations | POST | 200 | Create dedicated request schemas for bulk payloads |
| Status toggle/approve/cancel | POST | 200 | Path param `{id}`, return `{ModelName}Response` or `MessageResponse` |

### 4. Register the new schema

Add the class to `app/Http/Controllers/Api/SwaggerAnnotations.php`:

```php
\App\Swagger\{ModelName}Schemas::class,
```

### 5. Register the tag

Add a new `#[OA\Tag]` line to `app/Swagger/BaseSchemas.php` (before the `#[OA\Schema]` blocks):

```php
#[OA\Tag(name: "{Tag Name}", description: "{Short description}")]
```

**Do NOT put `#[OA\Tag]` in the individual schema file** — all tags are centrally managed in `BaseSchemas.php`.

### 6. Regenerate docs

// turbo
```
php artisan l5-swagger:generate
```

### 7. Verify

Check `storage/api-docs/api-docs.json` to confirm:
- The new tag appears in the `tags` array
- All endpoints appear under `paths`
- All schemas appear under `components.schemas`

## Property type reference

| PHP / Migration type | OA type | OA format | Example |
|---|---|---|---|
| `int`, `foreignId`, `id` | `"integer"` | — | `example: 1` |
| `string` | `"string"` | — | `example: "value"` |
| `string` (email) | `"string"` | `"email"` | `example: "a@b.com"` |
| `decimal`, `float` | `"number"` | `"float"` | `example: 100.00` |
| `boolean` | `"boolean"` | — | `example: true` |
| `date` | `"string"` | `"date"` | `example: "2024-01-15"` |
| `datetime`, `timestamp` | `"string"` | `"date-time"` | — |
| `text` | `"string"` | — | `nullable: true` |
| `enum` | `"string"` | — | `enum: ["a", "b"]` |
| `json`, `array` | `"array"` or `"object"` | — | Use `items` for arrays |

## Naming conventions

- Schema file: `{ModelName}Schemas.php` (PascalCase, plural "Schemas")
- Model schema: `"{ModelName}"` (e.g. `"Invoice"`)
- Store request: `"{ModelName}StoreRequest"`
- Update request: `"{ModelName}UpdateRequest"`
- Single response: `"{ModelName}Response"`
- List response: `"{ModelName}ListResponse"`
- Bulk request: `"{ModelName}Bulk{Action}Request"` (e.g. `"InvoiceBulkApproveRequest"`)
- Class name: `{ModelName}Schemas`
- Tag name: Plural display name (e.g. `"Invoices"`, `"Account Services"`)

## Reference examples

- Simple CRUD: `app/Swagger/BranchSchemas.php`, `app/Swagger/WarehouseSchemas.php`
- CRUD + search + statistics + bulk ops: `app/Swagger/ProductSchemas.php`, `app/Swagger/EmployeeSchemas.php`
- CRUD + approve/cancel/duplicate + PDF: `app/Swagger/InvoiceSchemas.php`
- Nested resource (tasks under projects): `app/Swagger/TaskSchemas.php`
- Extra actions (convert, toggle, set-base): `app/Swagger/CurrencySchemas.php`
