<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function checkIn(Employee $employee, array $data): Attendance
    {
        $today = now()->toDateString();

        // Check if already checked in
        $existing = Attendance::forEmployee($employee->id)
            ->forDate($today)
            ->first();

        if ($existing && $existing->isCheckedIn()) {
            throw new \Exception('Already checked in today');
        }

        $settings = $this->getSettings($employee->organization_id, $employee->branch_id);

        // Validate location if required
        if ($settings['require_gps'] ?? false) {
            $this->validateLocation($data, $settings);
        }

        // Validate IP if configured
        if (!empty($settings['allowed_ips'])) {
            $this->validateIP($data['ip'] ?? null, $settings['allowed_ips']);
        }

        $checkInTime = now();
        $lateMinutes = $this->calculateLateMinutes($checkInTime, $settings);
        $status = $lateMinutes > 0 ? Attendance::STATUS_LATE : Attendance::STATUS_PRESENT;

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'organization_id' => $employee->organization_id,
                'branch_id' => $employee->branch_id,
                'check_in' => $checkInTime->format('H:i:s'),
                'check_in_method' => $data['method'] ?? Attendance::METHOD_MANUAL,
                'check_in_latitude' => $data['latitude'] ?? null,
                'check_in_longitude' => $data['longitude'] ?? null,
                'check_in_ip' => $data['ip'] ?? null,
                'check_in_device' => $data['device'] ?? null,
                'is_remote' => $data['is_remote'] ?? false,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ]
        );

        return $attendance;
    }

    public function checkOut(Employee $employee, array $data): Attendance
    {
        $today = now()->toDateString();

        $attendance = Attendance::forEmployee($employee->id)
            ->forDate($today)
            ->first();

        if (!$attendance || !$attendance->isCheckedIn()) {
            throw new \Exception('You need to check in first');
        }

        if ($attendance->isCheckedOut()) {
            throw new \Exception('Already checked out today');
        }

        $settings = $this->getSettings($employee->organization_id, $employee->branch_id);
        $checkOutTime = now();
        $earlyLeaveMinutes = $this->calculateEarlyLeaveMinutes($checkOutTime, $settings);

        $attendance->update([
            'check_out' => $checkOutTime->format('H:i:s'),
            'check_out_method' => $data['method'] ?? Attendance::METHOD_MANUAL,
            'check_out_latitude' => $data['latitude'] ?? null,
            'check_out_longitude' => $data['longitude'] ?? null,
            'check_out_ip' => $data['ip'] ?? null,
            'check_out_device' => $data['device'] ?? null,
            'early_leave_minutes' => $earlyLeaveMinutes,
        ]);

        // Calculate worked time
        $attendance->calculateWorkedTime();
        
        // Calculate overtime
        $attendance->overtime_minutes = $this->calculateOvertime($attendance, $settings);
        
        // Update status if half day
        if ($attendance->worked_minutes < ($settings['standard_work_hours'] ?? 8) * 30) {
            $attendance->status = Attendance::STATUS_HALF_DAY;
        }

        $attendance->save();

        return $attendance;
    }

    public function getSettings(int $organizationId, ?int $branchId = null): array
    {
        // Would normally fetch from attendance_settings table
        return [
            'work_start_time' => '08:00:00',
            'work_end_time' => '17:00:00',
            'grace_period_minutes' => 15,
            'standard_work_hours' => 8,
            'allow_remote' => true,
            'require_gps' => false,
            'office_latitude' => null,
            'office_longitude' => null,
            'geo_fence_radius' => 100,
            'allowed_ips' => [],
        ];
    }

    protected function calculateLateMinutes(Carbon $checkIn, array $settings): int
    {
        $workStart = Carbon::parse($settings['work_start_time']);
        $gracePeriod = $settings['grace_period_minutes'] ?? 0;
        
        $allowedTime = $workStart->addMinutes($gracePeriod);
        
        if ($checkIn->gt($allowedTime)) {
            return $checkIn->diffInMinutes($allowedTime);
        }

        return 0;
    }

    protected function calculateEarlyLeaveMinutes(Carbon $checkOut, array $settings): int
    {
        $workEnd = Carbon::parse($settings['work_end_time']);
        
        if ($checkOut->lt($workEnd)) {
            return $workEnd->diffInMinutes($checkOut);
        }

        return 0;
    }

    protected function calculateOvertime(Attendance $attendance, array $settings): int
    {
        $standardMinutes = ($settings['standard_work_hours'] ?? 8) * 60;
        
        if ($attendance->worked_minutes > $standardMinutes) {
            return $attendance->worked_minutes - $standardMinutes;
        }

        return 0;
    }

    protected function validateLocation(array $data, array $settings): void
    {
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            throw new \Exception('Location is required');
        }

        if (!$settings['office_latitude'] || !$settings['office_longitude']) {
            return;
        }

        $distance = $this->calculateDistance(
            $data['latitude'],
            $data['longitude'],
            $settings['office_latitude'],
            $settings['office_longitude']
        );

        if ($distance > ($settings['geo_fence_radius'] ?? 100)) {
            throw new \Exception('You are outside the allowed area');
        }
    }

    protected function validateIP(?string $ip, array $allowedIPs): void
    {
        if (!$ip || !in_array($ip, $allowedIPs)) {
            throw new \Exception('Check-in from this IP is not allowed');
        }
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getMonthlyReport(int $employeeId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::forEmployee($employeeId)
            ->betweenDates($startDate->toDateString(), $endDate->toDateString())
            ->get();

        return [
            'employee_id' => $employeeId,
            'year' => $year,
            'month' => $month,
            'total_days' => $endDate->day,
            'working_days' => $this->getWorkingDays($startDate, $endDate),
            'present_days' => $attendances->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])->count(),
            'absent_days' => $attendances->where('status', Attendance::STATUS_ABSENT)->count(),
            'late_days' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'leave_days' => $attendances->where('status', Attendance::STATUS_LEAVE)->count(),
            'half_days' => $attendances->where('status', Attendance::STATUS_HALF_DAY)->count(),
            'total_worked_minutes' => $attendances->sum('worked_minutes'),
            'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'records' => $attendances,
        ];
    }

    protected function getWorkingDays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    public function markAbsent(int $organizationId, string $date): int
    {
        // Find employees who didn't check in
        $employees = Employee::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereDoesntHave('attendances', function ($q) use ($date) {
                $q->where('date', $date);
            })
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            Attendance::create([
                'organization_id' => $organizationId,
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'date' => $date,
                'status' => Attendance::STATUS_ABSENT,
            ]);
            $count++;
        }

        return $count;
    }
}
