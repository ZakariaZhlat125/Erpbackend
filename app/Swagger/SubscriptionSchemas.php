<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin/Subscriptions", description: "Admin subscription management endpoints")]
#[OA\Tag(name: "Subscriptions", description: "User subscription endpoints")]
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
class SubscriptionSchemas
{
}
