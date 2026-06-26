<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmaoMaintenanceRoute extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'gmao_equipment_category_id',
        'reference',
        'title',
        'frequency',
        'estimated_duration_hours',
        'status',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'estimated_duration_hours' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function equipmentCategory(): BelongsTo
    {
        return $this->belongsTo(GmaoEquipmentCategory::class, 'gmao_equipment_category_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(GmaoMaintenanceTask::class)->orderBy('position');
    }

    public function preventivePlans(): HasMany
    {
        return $this->hasMany(GmaoPreventivePlan::class, 'gmao_maintenance_route_id');
    }
}
