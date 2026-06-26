<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmaoPreventivePlan extends Model
{
    protected $fillable = [
        'company_site_id',
        'gmao_equipment_id',
        'gmao_maintenance_route_id',
        'reference',
        'title',
        'frequency',
        'trigger_type',
        'meter_interval',
        'alert_days',
        'last_done_at',
        'next_due_at',
        'status',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'meter_interval' => 'decimal:2',
            'alert_days' => 'integer',
            'last_done_at' => 'date',
            'next_due_at' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(GmaoEquipment::class, 'gmao_equipment_id');
    }

    public function maintenanceRoute(): BelongsTo
    {
        return $this->belongsTo(GmaoMaintenanceRoute::class, 'gmao_maintenance_route_id');
    }
}
