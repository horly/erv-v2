<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumanResourceProfileRecord extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'RH';

    public const TYPE_DOCUMENT = 'documents';
    public const TYPE_SALARY_ADVANCE = 'salary-advances';
    public const TYPE_PAYROLL_ADJUSTMENT = 'payroll-adjustments';
    public const TYPE_SCHEDULE = 'schedules';
    public const TYPE_EVALUATION = 'evaluations';
    public const TYPE_TRAINING = 'trainings';
    public const TYPE_SANCTION = 'sanctions';
    public const TYPE_RECRUITMENT = 'recruitment';
    public const TYPE_SETTING = 'settings';

    protected $fillable = [
        'company_site_id',
        'human_resource_employee_id',
        'created_by',
        'record_type',
        'reference',
        'title',
        'category',
        'status',
        'date_from',
        'date_to',
        'amount',
        'currency',
        'score',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'amount' => 'float',
            'score' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HumanResourceEmployee::class, 'human_resource_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
