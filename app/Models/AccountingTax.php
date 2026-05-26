<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTax extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'TAX';
    public const KIND_VAT = 'vat';
    public const KIND_WITHHOLDING = 'withholding';
    public const KIND_STAMP_DUTY = 'stamp_duty';
    public const KIND_SPECIAL = 'special';
    public const KIND_EXEMPTION = 'exemption';
    public const CALCULATION_PERCENTAGE = 'percentage';
    public const CALCULATION_FIXED = 'fixed';
    public const NATURE_COLLECTED = 'collected';
    public const NATURE_DEDUCTIBLE = 'deductible';
    public const NATURE_WITHHELD = 'withheld';
    public const APPLIES_SALES = 'sales';
    public const APPLIES_PURCHASES = 'purchases';
    public const APPLIES_BOTH = 'both';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'code',
        'name',
        'kind',
        'calculation_type',
        'value',
        'nature',
        'applies_to',
        'description',
        'is_default',
        'is_system_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_default' => 'boolean',
            'is_system_default' => 'boolean',
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

    public static function kinds(): array
    {
        return [
            self::KIND_VAT,
            self::KIND_WITHHOLDING,
            self::KIND_STAMP_DUTY,
            self::KIND_SPECIAL,
            self::KIND_EXEMPTION,
        ];
    }

    public static function calculationTypes(): array
    {
        return [self::CALCULATION_PERCENTAGE, self::CALCULATION_FIXED];
    }

    public static function natures(): array
    {
        return [self::NATURE_COLLECTED, self::NATURE_DEDUCTIBLE, self::NATURE_WITHHELD];
    }

    public static function applications(): array
    {
        return [self::APPLIES_SALES, self::APPLIES_PURCHASES, self::APPLIES_BOTH];
    }
}
