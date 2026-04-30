<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingStockInventory extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'INV';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['company_site_id', 'warehouse_id', 'created_by', 'reference', 'counted_at', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'counted_at' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingStockInventoryLine::class, 'inventory_id');
    }

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_CANCELLED];
    }
}
