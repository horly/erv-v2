<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingStockWarehouse extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'DEP';

    protected $fillable = ['company_site_id', 'created_by', 'reference', 'name', 'code', 'manager_name', 'address', 'status'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingStockItem::class, 'default_warehouse_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(AccountingStockBatch::class, 'warehouse_id');
    }
}
