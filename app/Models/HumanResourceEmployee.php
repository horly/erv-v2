<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HumanResourceEmployee extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_TERMINATED = 'terminated';

    public const EMPLOYMENT_FULL_TIME = 'full_time';
    public const EMPLOYMENT_PART_TIME = 'part_time';
    public const EMPLOYMENT_CONTRACTOR = 'contractor';
    public const EMPLOYMENT_INTERN = 'intern';

    protected $fillable = [
        'company_site_id',
        'human_resource_department_id',
        'user_id',
        'created_by',
        'employee_number',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'professional_email',
        'personal_email',
        'phone',
        'address',
        'job_title',
        'employment_type',
        'hire_date',
        'termination_date',
        'status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (HumanResourceEmployee $employee): void {
            if (filled($employee->employee_number)) {
                return;
            }

            $employee->forceFill([
                'employee_number' => self::employeeNumberFromId((int) $employee->id),
            ])->saveQuietly();
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HumanResourceDepartment::class, 'human_resource_department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(HumanResourceContract::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(HumanResourceContract::class)->where('status', HumanResourceContract::STATUS_ACTIVE)->latestOfMany();
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HumanResourceLeaveRequest::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(HumanResourceAttendance::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(HumanResourcePayrollEntry::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public static function employeeNumberFromId(int $id): string
    {
        return 'EMP-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
