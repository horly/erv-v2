<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingDeliveryNote extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'BL';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_site_id',
        'client_id',
        'customer_order_id',
        'created_by',
        'reference',
        'title',
        'delivery_date',
        'status',
        'delivered_by',
        'carrier',
        'stock_released_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'stock_released_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'client_id');
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(AccountingCustomerOrder::class, 'customer_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingDeliveryNoteLine::class, 'delivery_note_id');
    }

    public function isStockReleased(): bool
    {
        return $this->stock_released_at !== null;
    }

    public function releasesStock(): bool
    {
        return in_array($this->status, [self::STATUS_PARTIAL, self::STATUS_DELIVERED], true);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_READY,
            self::STATUS_PARTIAL,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }
}
