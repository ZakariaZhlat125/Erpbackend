<?php

namespace App\Repositories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyRepository extends BaseRepository
{
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model->active()->orderBy('name')->get();
    }

    public function getBaseCurrency(): ?Currency
    {
        return $this->model->where('is_base', true)->first();
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->model->where('code', $code)->first();
    }

    public function updateExchangeRate(int $id, float $rate): bool
    {
        return $this->model->findOrFail($id)->update(['exchange_rate' => $rate]);
    }

    public function setAsBase(int $id): bool
    {
        // First, set all currencies to not base
        $this->model->where('is_base', true)->update(['is_base' => false]);
        
        // Then set the specified currency as base
        return $this->model->findOrFail($id)->update([
            'is_base' => true,
            'exchange_rate' => 1.000000,
        ]);
    }

    public function toggleActive(int $id): bool
    {
        $currency = $this->model->findOrFail($id);
        return $currency->update(['is_active' => !$currency->is_active]);
    }
}
