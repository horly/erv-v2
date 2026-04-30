<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingStockBatch extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'LOT';

    protected $fillable = ['company_site_id', 'item_id', 'warehouse_id', 'created_by', 'reference', 'batch_number', 'serial_number', 'expires_at', 'quantity', 'status'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'quantity' => 'float',
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
}
