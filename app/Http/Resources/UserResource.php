<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    properties: [
        new OA\Property(property: "id", type: "string", example: "1"),
        new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "avatar", type: "string", nullable: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "establishments", type: "array", items: new OA\Items(type: "object")),
    ]
)]
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'roles' => $this->getRoleNames()->toArray(),
            'permissions' => $this->getAllPermissions()->pluck('name')->toArray(),
            'establishments' => $this->when(
                $this->relationLoaded('organizations'),
                fn() => $this->organizations->map(fn($org) => [
                    'id' => (string) $org->id,
                    'name' => $org->name,
                    'isActive' => (bool) ($org->pivot->is_active ?? true),
                ])->toArray(),
                []
            ),
        ];
    }
}
