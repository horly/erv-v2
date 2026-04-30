<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingStockAlert extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ALT';
    public const TYPE_LOW_STOCK = 'low_stock';
    public const TYPE_OVERSTOCK = 'overstock';
    public const TYPE_EXPIRATION = 'expiration';

    protected $fillable = ['company_site_id', 'item_id', 'warehouse_id', 'created_by', 'reference', 'type', 'threshold_quantity', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'threshold_quantity' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingStockItem::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [self::TYPE_LOW_STOCK, self::TYPE_OVERSTOCK, self::TYPE_EXPIRATION];
    }
}
