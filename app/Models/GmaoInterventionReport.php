<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmaoInterventionReport extends Model
{
    protected $fillable = [
        'company_site_id',
        'gmao_work_order_id',
        'gmao_technician_id',
        'reference',
        'diagnosis',
        'work_done',
        'recommendations',
        'result',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(GmaoWorkOrder::class, 'gmao_work_order_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(GmaoTechnician::class, 'gmao_technician_id');
    }
}
