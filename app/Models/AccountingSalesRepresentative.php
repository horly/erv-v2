<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSalesRepresentative extends Model
{
    use HasFactory;

    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_INDEPENDENT_AGENT = 'independent_agent';
    public const TYPE_RESELLER = 'reseller';
    public const TYPE_BUSINESS_REFERRER = 'business_referrer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'type',
        'name',
        'phone',
        'email',
        'address',
        'sales_area',
        'currency',
        'monthly_target',
        'annual_target',
        'commission_rate',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_target' => 'float',
            'annual_target' => 'float',
            'commission_rate' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AccountingSalesRepresentative $representative): void {
            if (filled($representative->reference)) {
                return;
            }

            $representative->forceFill([
                'reference' => self::referenceFromId((int) $representative->id),
            ])->saveQuietly();
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [
            self::TYPE_INTERNAL,
            self::TYPE_EXTERNAL,
            self::TYPE_INDEPENDENT_AGENT,
            self::TYPE_RESELLER,
            self::TYPE_BUSINESS_REFERRER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_INACTIVE,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'COM-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
