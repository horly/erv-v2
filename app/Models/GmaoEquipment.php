<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmaoEquipment extends Model
{
    public const STATUS_OPERATIONAL = 'operational';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_DOWN = 'down';
    public const STATUS_MAINTENANCE = 'maintenance';

    protected $table = 'gmao_equipment';

    protected $fillable = [
        'company_site_id',
        'gmao_location_id',
        'gmao_equipment_category_id',
        'reference',
        'asset_code',
        'name',
        'category',
        'criticality',
        'brand',
        'model',
        'serial_number',
        'supplier',
        'acquisition_cost',
        'expense_type',
        'cost_center',
        'meter_unit',
        'current_meter',
        'last_meter_read_at',
        'expected_lifetime_months',
        'commissioned_at',
        'warranty_until',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_cost' => 'decimal:2',
            'current_meter' => 'decimal:2',
            'last_meter_read_at' => 'datetime',
            'expected_lifetime_months' => 'integer',
            'commissioned_at' => 'date',
            'warranty_until' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GmaoLocation::class, 'gmao_location_id');
    }

    public function equipmentCategory(): BelongsTo
    {
        return $this->belongsTo(GmaoEquipmentCategory::class, 'gmao_equipment_category_id');
    }

    public function workRequests(): HasMany
    {
        return $this->hasMany(GmaoWorkRequest::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(GmaoWorkOrder::class);
    }

    public function preventivePlans(): HasMany
    {
        return $this->hasMany(GmaoPreventivePlan::class);
    }
}
