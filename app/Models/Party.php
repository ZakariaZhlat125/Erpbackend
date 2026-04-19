<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Party extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'type',
        'display_name',
        'legal_name',
        'tax_number',
        'default_currency',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check() && auth()->user()->organization_id) {
                $builder->where('parties.organization_id', auth()->user()->organization_id);
            }
        });

        static::creating(function ($party) {
            if (empty($party->organization_id) && auth()->check()) {
                $party->organization_id = auth()->user()->organization_id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(PartyRole::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeCustomers($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('role', 'customer');
        });
    }

    public function scopeSuppliers($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('role', 'supplier');
        });
    }

    public function scopeByRole($query, string $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('role', $role);
        });
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('role', $role)->exists();
    }
}
