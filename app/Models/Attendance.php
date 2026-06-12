<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'branch_id',
        'date',
        'check_in',
        'check_out',
        'check_in_method',
        'check_out_method',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_ip',
        'check_out_ip',
        'check_in_device',
        'check_out_device',
        'status',
        'worked_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_leave_minutes',
        'notes',
        'is_remote',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime:H:i:s',
            'check_out' => 'datetime:H:i:s',
            'check_in_latitude' => 'decimal:8',
            'check_in_longitude' => 'decimal:8',
            'check_out_latitude' => 'decimal:8',
            'check_out_longitude' => 'decimal:8',
            'worked_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'is_remote' => 'boolean',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_LEAVE = 'leave';
    public const STATUS_HOLIDAY = 'holiday';
    public const STATUS_WEEKEND = 'weekend';

    // ==================== Methods ====================
    public const METHOD_GPS = 'gps';
    public const METHOD_FINGERPRINT = 'fingerprint';
    public const METHOD_QR = 'qr';
    public const METHOD_MANUAL = 'manual';
    public const METHOD_IP = 'ip';
    public const METHOD_REMOTE = 'remote';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ==================== Scopes ====================
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    // ==================== Helpers ====================
    public function isCheckedIn(): bool
    {
        return $this->check_in !== null;
    }

    public function isCheckedOut(): bool
    {
        return $this->check_out !== null;
    }

    public function getWorkedHours(): float
    {
        return round($this->worked_minutes / 60, 2);
    }

    public function getOvertimeHours(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }

    public function calculateWorkedTime(): void
    {
        if (!$this->check_in || !$this->check_out) {
            return;
        }

        $checkIn = \Carbon\Carbon::parse($this->check_in);
        $checkOut = \Carbon\Carbon::parse($this->check_out);
        
        $this->worked_minutes = $checkOut->diffInMinutes($checkIn);
    }

    public function getCheckInLocation(): ?array
    {
        if (!$this->check_in_latitude || !$this->check_in_longitude) {
            return null;
        }

        return [
            'latitude' => $this->check_in_latitude,
            'longitude' => $this->check_in_longitude,
        ];
    }

    public function getCheckOutLocation(): ?array
    {
        if (!$this->check_out_latitude || !$this->check_out_longitude) {
            return null;
        }

        return [
            'latitude' => $this->check_out_latitude,
            'longitude' => $this->check_out_longitude,
        ];
    }
}
