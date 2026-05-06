<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingProformaInvoiceLine extends Model
{
    use HasFactory;

    public const TYPE_ITEM = 'item';
    public const TYPE_SERVICE = 'service';
    public const TYPE_FREE = 'free';
    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENT = 'percent';

    protected $fillable = [
        'proforma_invoice_id',
        'line_type',
        'item_id',
        'service_id',
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

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(AccountingProformaInvoice::class, 'proforma_invoice_id');
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
}
