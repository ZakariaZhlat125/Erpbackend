<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'decimal_separator' => $this->decimal_separator,
            'thousands_separator' => $this->thousands_separator,
            'decimal_places' => $this->decimal_places,
            'exchange_rate' => $this->exchange_rate,
            'is_base' => $this->is_base,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
