<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingStockInventoryLine extends Model
{
    use HasFactory;

    protected $fillable = ['inventory_id', 'item_id', 'expected_quantity', 'counted_quantity', 'difference_quantity', 'notes'];

    protected function casts(): array
    {
        return [
            'expected_quantity' => 'float',
            'counted_quantity' => 'float',
            'difference_quantity' => 'float',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(AccountingStockInventory::class, 'inventory_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingStockItem::class, 'item_id');
    }
}
