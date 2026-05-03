<?php

namespace App\Services;

use App\Repositories\CurrencyRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Currency;

class CurrencyService extends BaseService
{
    public function __construct(CurrencyRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    public function getBaseCurrency(): ?Currency
    {
        return $this->repository->getBaseCurrency();
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->repository->findByCode($code);
    }

    public function updateExchangeRate(int $id, float $rate): bool
    {
        return $this->repository->updateExchangeRate($id, $rate);
    }

    public function setAsBase(int $id): bool
    {
        // Ensure the currency cannot be set as base if it's inactive
        $currency = $this->repository->find($id);
        
        if (!$currency->is_active) {
            throw new \Exception('Cannot set inactive currency as base currency');
        }

        return $this->repository->setAsBase($id);
    }

    public function toggleActive(int $id): bool
    {
        $currency = $this->repository->find($id);
        
        // Prevent deactivating the base currency
        if ($currency->is_base && $currency->is_active) {
            throw new \Exception('Cannot deactivate the base currency');
        }

        return $this->repository->toggleActive($id);
    }

    public function convert(int $fromCurrencyId, int $toCurrencyId, float $amount): float
    {
        $fromCurrency = $this->repository->find($fromCurrencyId);
        $toCurrency = $this->repository->find($toCurrencyId);

        return $fromCurrency->convertTo($toCurrency, $amount);
    }

    public function formatAmount(int $currencyId, float $amount): string
    {
        $currency = $this->repository->find($currencyId);
        return $currency->formatAmount($amount);
    }
}
