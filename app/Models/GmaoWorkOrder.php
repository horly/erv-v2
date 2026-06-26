<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GmaoWorkOrder extends Model
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_site_id',
        'gmao_work_request_id',
        'gmao_equipment_id',
        'gmao_technician_id',
        'created_by',
        'reference',
        'title',
        'type',
        'priority',
        'status',
        'workflow_stage',
        'estimated_hours',
        'actual_hours',
        'failure_started_at',
        'planned_at',
        'started_at',
        'completed_at',
        'downtime_minutes',
        'labor_cost',
        'external_cost',
        'capex_amount',
        'opex_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'failure_started_at' => 'datetime',
            'planned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'downtime_minutes' => 'integer',
            'labor_cost' => 'decimal:2',
            'external_cost' => 'decimal:2',
            'capex_amount' => 'decimal:2',
            'opex_amount' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(GmaoWorkRequest::class, 'gmao_work_request_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(GmaoEquipment::class, 'gmao_equipment_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(GmaoTechnician::class, 'gmao_technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(GmaoWorkOrderPart::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(GmaoInterventionReport::class);
    }
}
