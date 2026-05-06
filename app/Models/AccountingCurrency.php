<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingCurrency extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CUR';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_base' => 'boolean',
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
}
