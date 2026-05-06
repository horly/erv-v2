<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingStockCategory extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CAT';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = ['company_site_id', 'warehouse_id', 'created_by', 'reference', 'name', 'description', 'status', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'warehouse_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(AccountingStockSubcategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingStockItem::class, 'category_id');
    }

    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }
}
