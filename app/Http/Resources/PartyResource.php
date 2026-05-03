<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'type' => $this->type,
            'display_name' => $this->display_name,
            'legal_name' => $this->legal_name,
            'tax_number' => $this->tax_number,
            'default_currency' => $this->default_currency,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships (when loaded)
            'roles' => $this->when($this->relationLoaded('roles'), function () {
                return $this->roles->pluck('role');
            }),
            'contacts' => $this->when($this->relationLoaded('contacts'), function () {
                return $this->contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'email' => $contact->email,
                        'phone' => $contact->phone,
                        'position' => $contact->position,
                        'is_primary' => $contact->is_primary,
                    ];
                });
            }),
            'addresses' => $this->when($this->relationLoaded('addresses'), function () {
                return $this->addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'label' => $address->label,
                        'country' => $address->country,
                        'city' => $address->city,
                        'line_1' => $address->line_1,
                        'line_2' => $address->line_2,
                        'postal_code' => $address->postal_code,
                        'is_primary' => $address->is_primary,
                    ];
                });
            }),
        ];
    }
}
