<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingStockMovement extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'MVT';
    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = ['company_site_id', 'item_id', 'warehouse_id', 'batch_id', 'created_by', 'reference', 'type', 'quantity', 'movement_date', 'reason', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'movement_date' => 'date',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AccountingStockBatch::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [self::TYPE_ENTRY, self::TYPE_EXIT, self::TYPE_ADJUSTMENT];
    }
}
