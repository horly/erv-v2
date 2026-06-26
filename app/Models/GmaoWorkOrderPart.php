<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmaoWorkOrderPart extends Model
{
    protected $fillable = [
        'gmao_work_order_id',
        'gmao_spare_part_id',
        'quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(GmaoWorkOrder::class, 'gmao_work_order_id');
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(GmaoSparePart::class, 'gmao_spare_part_id');
    }
}
