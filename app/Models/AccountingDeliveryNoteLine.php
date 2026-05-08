<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingDeliveryNoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id',
        'customer_order_line_id',
        'line_type',
        'item_id',
        'service_id',
        'description',
        'details',
        'ordered_quantity',
        'already_delivered_quantity',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:2',
            'already_delivered_quantity' => 'decimal:2',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(AccountingDeliveryNote::class, 'delivery_note_id');
    }

    public function customerOrderLine(): BelongsTo
    {
        return $this->belongsTo(AccountingCustomerOrderLine::class, 'customer_order_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingStockItem::class, 'item_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AccountingService::class, 'service_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(AccountingDeliveryNoteSerial::class, 'delivery_note_line_id')->orderBy('position');
    }
}
