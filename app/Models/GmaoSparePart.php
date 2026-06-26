<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmaoSparePart extends Model
{
    protected $fillable = [
        'company_site_id',
        'reference',
        'name',
        'category',
        'unit',
        'stock_quantity',
        'minimum_quantity',
        'unit_cost',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:2',
            'minimum_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function workOrderParts(): HasMany
    {
        return $this->hasMany(GmaoWorkOrderPart::class);
    }
}
