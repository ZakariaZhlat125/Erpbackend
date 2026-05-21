<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Subscription",
    required: ["id", "user_id", "plan_id", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(
            property: "user",
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "John Doe"),
                new OA\Property(property: "email", type: "string", example: "john@example.com"),
            ]
        ),
        new OA\Property(property: "plan_id", type: "integer", example: 1),
        new OA\Property(
            property: "plan",
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Premium Plan"),
                new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
                new OA\Property(property: "billing_cycle", type: "string", example: "monthly"),
            ]
        ),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-02-01"),
        new OA\Property(property: "trial_ends_at", type: "string", format: "date", nullable: true, example: "2024-01-15"),
        new OA\Property(property: "status", type: "string", enum: ["active", "expired", "cancelled", "trial"], example: "active"),
        new OA\Property(property: "auto_renew", type: "boolean", example: true),
        new OA\Property(property: "price_paid", type: "number", format: "float", example: 99.99),
        new OA\Property(property: "billing_cycle", type: "string", example: "monthly"),
        new OA\Property(property: "cancelled_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "cancellation_reason", type: "string", nullable: true, example: "Too expensive"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_expired", type: "boolean", example: false),
        new OA\Property(property: "is_cancelled", type: "boolean", example: false),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "StoreSubscriptionRequest",
    required: ["user_id", "plan_id", "start_date", "end_date", "status", "price_paid", "billing_cycle"],
    properties: [
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "plan_id", type: "integer", example: 1),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-02-01"),
        new OA\Property(property: "trial_ends_at", type: "string", format: "date", nullable: true, example: "2024-01-15"),
        new OA\Property(property: "status", type: "string", enum: ["active", "expired", "cancelled", "trial"], example: "active"),
        new OA\Property(property: "auto_renew", type: "boolean", example: true),
        new OA\Property(property: "price_paid", type: "number", format: "float", example: 99.99),
        new OA\Property(property: "billing_cycle", type: "string", enum: ["monthly", "yearly", "lifetime"], example: "monthly"),
    ]
)]
#[OA\Schema(
    schema: "UpdateSubscriptionRequest",
    properties: [
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "plan_id", type: "integer", example: 1),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-02-01"),
        new OA\Property(property: "trial_ends_at", type: "string", format: "date", nullable: true, example: "2024-01-15"),
        new OA\Property(property: "status", type: "string", enum: ["active", "expired", "cancelled", "trial"], example: "active"),
        new OA\Property(property: "auto_renew", type: "boolean", example: true),
        new OA\Property(property: "price_paid", type: "number", format: "float", example: 99.99),
        new OA\Property(property: "billing_cycle", type: "string", enum: ["monthly", "yearly", "lifetime"], example: "monthly"),
        new OA\Property(property: "cancellation_reason", type: "string", nullable: true, example: "Changed my mind"),
    ]
)]
#[OA\Schema(
    schema: "SubscribeRequest",
    required: ["plan_id"],
    properties: [
        new OA\Property(property: "plan_id", type: "integer", example: 1),
        new OA\Property(property: "is_trial", type: "boolean", example: false),
    ]
)]
#[OA\Schema(
    schema: "UnsubscribeRequest",
    properties: [
        new OA\Property(property: "reason", type: "string", nullable: true, example: "Too expensive"),
    ]
)]
#[OA\Schema(
    schema: "ChangeStatusRequest",
    required: ["status"],
    properties: [
        new OA\Property(property: "status", type: "string", enum: ["active", "expired", "cancelled", "trial"], example: "active"),
    ]
)]
#[OA\Schema(
    schema: "SubscribeUserRequest",
    required: ["user_id", "plan_id"],
    properties: [
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "plan_id", type: "integer", example: 1),
        new OA\Property(property: "is_trial", type: "boolean", example: false),
    ]
)]
#[OA\Schema(
    schema: "SubscriptionResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Subscription"),
    ]
)]
#[OA\Schema(
    schema: "SubscriptionListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Subscription")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]

// --- Admin Subscription endpoints ---

#[OA\Get(
    path: "/admin/subscriptions",
    summary: "Get all subscriptions",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionListResponse")),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
#[OA\Post(
    path: "/admin/subscriptions",
    summary: "Create a new subscription",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/StoreSubscriptionRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Subscription created", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Get(
    path: "/admin/subscriptions/{id}",
    summary: "Get subscription by ID",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Put(
    path: "/admin/subscriptions/{id}",
    summary: "Update subscription",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/UpdateSubscriptionRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Subscription updated", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Delete(
    path: "/admin/subscriptions/{id}",
    summary: "Delete subscription",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Subscription deleted"),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Patch(
    path: "/admin/subscriptions/{id}/change-status",
    summary: "Change subscription status",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/ChangeStatusRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Status changed", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Post(
    path: "/admin/subscriptions/subscribe-user",
    summary: "Subscribe a user to a plan",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/SubscribeUserRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "User subscribed", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Post(
    path: "/admin/subscriptions/{id}/renew",
    summary: "Renew subscription",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Subscription renewed", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Post(
    path: "/admin/subscriptions/{id}/cancel",
    summary: "Cancel subscription",
    security: [["sanctum" => []]],
    tags: ["Admin/Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(ref: "#/components/schemas/UnsubscribeRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Subscription cancelled", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]

// --- User Subscription endpoints ---

#[OA\Get(
    path: "/subscriptions/my-subscription",
    summary: "Get user's active subscription",
    security: [["sanctum" => []]],
    tags: ["Subscriptions"],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
#[OA\Post(
    path: "/subscriptions/subscribe",
    summary: "Subscribe to a plan",
    security: [["sanctum" => []]],
    tags: ["Subscriptions"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/SubscribeRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Subscription created", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Post(
    path: "/subscriptions/{id}/unsubscribe",
    summary: "Cancel subscription",
    security: [["sanctum" => []]],
    tags: ["Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(ref: "#/components/schemas/UnsubscribeRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Subscription cancelled"),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Post(
    path: "/subscriptions/{id}/renew",
    summary: "Renew subscription",
    security: [["sanctum" => []]],
    tags: ["Subscriptions"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Subscription renewed", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionResponse")),
        new OA\Response(response: 404, description: "Not found"),
    ]
)]
#[OA\Get(
    path: "/subscriptions/my-subscription-history",
    summary: "Get subscription history",
    security: [["sanctum" => []]],
    tags: ["Subscriptions"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/SubscriptionListResponse")),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
class SubscriptionSchemas
{
    // Subscription schemas and endpoint documentation.
}
