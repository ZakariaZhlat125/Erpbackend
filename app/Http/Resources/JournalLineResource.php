<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'account_id' => $this->account_id,
            'cost_center_id' => $this->cost_center_id,
            'line_number' => $this->line_number,
            'description' => $this->description,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'debit_fc' => $this->debit_fc,
            'credit_fc' => $this->credit_fc,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'party_id' => $this->party_id,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_amount' => $this->tax_amount,
            'reference' => $this->reference,
            'due_date' => $this->due_date?->format('Y-m-d'),

            'account' => $this->when($this->relationLoaded('account'), fn() => [
                'id' => $this->account->id,
                'code' => $this->account->code,
                'name' => $this->account->name,
            ]),
            'cost_center' => $this->when($this->relationLoaded('costCenter') && $this->costCenter, fn() => [
                'id' => $this->costCenter->id,
                'code' => $this->costCenter->code,
                'name' => $this->costCenter->name,
            ]),
            'party' => $this->when($this->relationLoaded('party') && $this->party, fn() => [
                'id' => $this->party->id,
                'name' => $this->party->name,
            ]),
        ];
    }
}
