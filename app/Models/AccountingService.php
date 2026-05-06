<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingService extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'SRV';
    public const BILLING_FIXED = 'fixed';
    public const BILLING_HOURLY = 'hourly';
    public const BILLING_DAILY = 'daily';
    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'category_id',
        'subcategory_id',
        'unit_id',
        'created_by',
        'reference',
        'name',
        'billing_type',
        'price',
        'currency',
        'tax_rate',
        'estimated_duration',
        'status',
        'description',
        'internal_notes',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingServiceCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(AccountingServiceSubcategory::class, 'subcategory_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(AccountingServiceUnit::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recurringServices(): HasMany
    {
        return $this->hasMany(AccountingRecurringService::class, 'service_id');
    }

    public static function billingTypes(): array
    {
        return [
            self::BILLING_FIXED,
            self::BILLING_HOURLY,
            self::BILLING_DAILY,
            self::BILLING_MONTHLY,
            self::BILLING_YEARLY,
        ];
    }
}
