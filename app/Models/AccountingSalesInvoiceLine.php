<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSalesInvoiceLine extends Model
{
    use HasFactory;

    public const TYPE_ITEM = 'item';
    public const TYPE_SERVICE = 'service';
    public const TYPE_FREE = 'free';
    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENT = 'percent';

    protected $fillable = [
        'sales_invoice_id',
        'line_type',
        'item_id',
        'service_id',
        'customer_order_line_id',
        'delivery_note_line_id',
        'description',
        'details',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(AccountingSalesInvoice::class, 'sales_invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingStockItem::class, 'item_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AccountingService::class, 'service_id');
    }

    public function customerOrderLine(): BelongsTo
    {
        return $this->belongsTo(AccountingCustomerOrderLine::class, 'customer_order_line_id');
    }

    public function deliveryNoteLine(): BelongsTo
    {
        return $this->belongsTo(AccountingDeliveryNoteLine::class, 'delivery_note_line_id');
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
}
