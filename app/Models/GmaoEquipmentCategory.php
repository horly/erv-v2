<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmaoEquipmentCategory extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'reference',
        'code',
        'name',
        'family',
        'default_criticality',
        'description',
        'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(GmaoEquipment::class, 'gmao_equipment_category_id');
    }
}
