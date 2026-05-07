<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingCustomerOrderLine extends Model
{
    use HasFactory;

    public const TYPE_ITEM = 'item';
    public const TYPE_SERVICE = 'service';
    public const TYPE_FREE = 'free';
    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENT = 'percent';
    public const MARGIN_FIXED = 'fixed';
    public const MARGIN_PERCENT = 'percent';

    protected $fillable = [
        'customer_order_id',
        'line_type',
        'item_id',
        'service_id',
        'description',
        'details',
        'quantity',
        'cost_price',
        'unit_price',
        'margin_type',
        'margin_value',
        'discount_type',
        'discount_amount',
        'cost_total',
        'margin_total',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'margin_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'margin_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(AccountingCustomerOrder::class, 'customer_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingStockItem::class, 'item_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AccountingService::class, 'service_id');
    }

    public static function types(): array
    {
        return [
            self::TYPE_ITEM,
            self::TYPE_SERVICE,
            self::TYPE_FREE,
        ];
    }

    public static function discountTypes(): array
    {
        return [
            self::DISCOUNT_FIXED,
            self::DISCOUNT_PERCENT,
        ];
    }

    public static function marginTypes(): array
    {
        return [
            self::MARGIN_FIXED,
            self::MARGIN_PERCENT,
        ];
    }
}
