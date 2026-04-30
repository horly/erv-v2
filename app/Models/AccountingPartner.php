<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPartner extends Model
{
    use HasFactory;

    public const TYPE_BUSINESS_REFERRER = 'business_referrer';
    public const TYPE_DISTRIBUTOR = 'distributor';
    public const TYPE_SUBCONTRACTOR = 'subcontractor';
    public const TYPE_CONSULTING_FIRM = 'consulting_firm';
    public const TYPE_INSTITUTION = 'institution';
    public const TYPE_BANK = 'bank';
    public const TYPE_AGENCY = 'agency';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISCUSSION = 'discussion';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'type',
        'name',
        'contact_name',
        'contact_position',
        'phone',
        'email',
        'address',
        'website',
        'activity_domain',
        'partnership_started_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'partnership_started_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AccountingPartner $partner): void {
            if (filled($partner->reference)) {
                return;
            }

            $partner->forceFill([
                'reference' => self::referenceFromId((int) $partner->id),
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
            self::TYPE_BUSINESS_REFERRER,
            self::TYPE_DISTRIBUTOR,
            self::TYPE_SUBCONTRACTOR,
            self::TYPE_CONSULTING_FIRM,
            self::TYPE_INSTITUTION,
            self::TYPE_BANK,
            self::TYPE_AGENCY,
            self::TYPE_OTHER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_DISCUSSION,
            self::STATUS_SUSPENDED,
            self::STATUS_ENDED,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'PAR-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
