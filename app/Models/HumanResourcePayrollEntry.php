<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumanResourcePayrollEntry extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'PAIE';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'human_resource_employee_id',
        'human_resource_contract_id',
        'created_by',
        'reference',
        'period_month',
        'payment_date',
        'status',
        'currency',
        'gross_salary',
        'allowances',
        'deductions',
        'net_salary',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'payment_date' => 'date',
            'gross_salary' => 'float',
            'allowances' => 'float',
            'deductions' => 'float',
            'net_salary' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HumanResourceEmployee::class, 'human_resource_employee_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(HumanResourceContract::class, 'human_resource_contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
