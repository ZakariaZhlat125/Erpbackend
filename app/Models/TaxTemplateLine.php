<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTemplateLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_template_id',
        'tax_rate_id',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    // ==================== Relationships ====================
    public function template(): BelongsTo
    {
        return $this->belongsTo(TaxTemplate::class, 'tax_template_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
