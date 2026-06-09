<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumanResourceContract extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CTR';

    public const TYPE_PERMANENT = 'permanent';
    public const TYPE_FIXED_TERM = 'fixed_term';
    public const TYPE_CONSULTANT = 'consultant';
    public const TYPE_INTERNSHIP = 'internship';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'human_resource_employee_id',
        'created_by',
        'reference',
        'type',
        'status',
        'start_date',
        'end_date',
        'probation_end_date',
        'currency',
        'monthly_salary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'monthly_salary' => 'float',
        ];
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
