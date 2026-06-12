<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values_json',
        'new_values_json',
        'meta_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values_json' => 'array',
            'new_values_json' => 'array',
            'meta_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ==================== Actions ====================
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_EXPORTED = 'exported';
    public const ACTION_IMPORTED = 'imported';
    public const ACTION_PERMISSION_CHANGED = 'permission_changed';
    public const ACTION_PRICE_CHANGED = 'price_changed';
    public const ACTION_STATUS_CHANGED = 'status_changed';

    // ==================== Sensitive Actions ====================
    public const SENSITIVE_ACTIONS = [
        self::ACTION_DELETED,
        self::ACTION_LOGIN_FAILED,
        self::ACTION_PASSWORD_RESET,
        self::ACTION_APPROVED,
        self::ACTION_CANCELLED,
        self::ACTION_PERMISSION_CHANGED,
        self::ACTION_PRICE_CHANGED,
    ];

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    // ==================== Scopes ====================
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeByActor($query, int $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeBySubject($query, string $subjectType, ?int $subjectId = null)
    {
        $query->where('subject_type', $subjectType);
        
        if ($subjectId !== null) {
            $query->where('subject_id', $subjectId);
        }
        
        return $query;
    }

    public function scopeSensitive($query)
    {
        return $query->whereIn('action', self::SENSITIVE_ACTIONS);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    // ==================== Helpers ====================
    public function isSensitive(): bool
    {
        return in_array($this->action, self::SENSITIVE_ACTIONS);
    }

    public function getChangedFields(): array
    {
        if (!$this->old_values_json || !$this->new_values_json) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values_json as $key => $newValue) {
            $oldValue = $this->old_values_json[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }
        return $changed;
    }

    public function getDeviceInfo(): array
    {
        $userAgent = $this->user_agent ?? '';
        
        return [
            'browser' => $this->extractBrowser($userAgent),
            'os' => $this->extractOS($userAgent),
            'device' => $this->extractDevice($userAgent),
            'raw' => $userAgent,
        ];
    }

    protected function extractBrowser(string $userAgent): string
    {
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Opera|OPR/i', $userAgent)) return 'Opera';
        return 'Unknown';
    }

    protected function extractOS(string $userAgent): string
    {
        if (preg_match('/Windows/i', $userAgent)) return 'Windows';
        if (preg_match('/Mac/i', $userAgent)) return 'MacOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        if (preg_match('/iOS|iPhone|iPad/i', $userAgent)) return 'iOS';
        return 'Unknown';
    }

    protected function extractDevice(string $userAgent): string
    {
        if (preg_match('/Mobile/i', $userAgent)) return 'Mobile';
        if (preg_match('/Tablet/i', $userAgent)) return 'Tablet';
        return 'Desktop';
    }
}
