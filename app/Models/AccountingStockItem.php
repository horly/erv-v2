<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingStockItem extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ART';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_CONSUMABLE = 'consumable';
    public const TYPE_SERVICE_ITEM = 'service_item';

    protected $fillable = [
        'company_site_id',
        'category_id',
        'subcategory_id',
        'unit_id',
        'default_warehouse_id',
        'created_by',
        'reference',
        'sku',
        'barcode',
        'name',
        'type',
        'purchase_price',
        'sale_price',
        'current_stock',
        'min_stock',
        'currency',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'sale_price' => 'float',
            'current_stock' => 'float',
            'min_stock' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingStockCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(AccountingStockSubcategory::class, 'subcategory_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(AccountingStockUnit::class, 'unit_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'default_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(AccountingStockBatch::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AccountingStockMovement::class, 'item_id');
    }

    public static function types(): array
    {
        return [self::TYPE_PRODUCT, self::TYPE_CONSUMABLE, self::TYPE_SERVICE_ITEM];
    }
}
