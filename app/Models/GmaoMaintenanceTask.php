<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmaoMaintenanceTask extends Model
{
    protected $fillable = [
        'gmao_maintenance_route_id',
        'position',
        'title',
        'instructions',
        'safety_notes',
        'estimated_minutes',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(GmaoMaintenanceRoute::class, 'gmao_maintenance_route_id');
    }
}
