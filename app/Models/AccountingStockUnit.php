<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingStockUnit extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'UNT';
    public const TYPE_UNIT = 'unit';
    public const TYPE_WEIGHT = 'weight';
    public const TYPE_VOLUME = 'volume';
    public const TYPE_LENGTH = 'length';
    public const TYPE_PACKAGE = 'package';

    protected $fillable = ['company_site_id', 'created_by', 'reference', 'name', 'symbol', 'type', 'status'];

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
        return $this->hasMany(AccountingStockItem::class, 'unit_id');
    }

    public static function types(): array
    {
        return [self::TYPE_UNIT, self::TYPE_WEIGHT, self::TYPE_VOLUME, self::TYPE_LENGTH, self::TYPE_PACKAGE];
    }
}
