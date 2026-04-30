<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingStockTransfer extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'TRF';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['company_site_id', 'item_id', 'from_warehouse_id', 'to_warehouse_id', 'created_by', 'reference', 'quantity', 'transfer_date', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'transfer_date' => 'date',
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

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(AccountingStockWarehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_CANCELLED];
    }
}
