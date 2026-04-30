<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingProspect extends Model
{
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_COMPANY = 'company';

    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_CALL = 'call';
    public const SOURCE_SOCIAL = 'social';
    public const SOURCE_EVENT = 'event';
    public const SOURCE_CAMPAIGN = 'campaign';
    public const SOURCE_OTHER = 'other';

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_PROPOSAL_SENT = 'proposal_sent';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public const INTEREST_COLD = 'cold';
    public const INTEREST_WARM = 'warm';
    public const INTEREST_HOT = 'hot';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'converted_client_id',
        'reference',
        'type',
        'name',
        'profession',
        'phone',
        'email',
        'address',
        'rccm',
        'id_nat',
        'nif',
        'website',
        'source',
        'status',
        'interest_level',
        'notes',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AccountingProspect $prospect): void {
            if (filled($prospect->reference)) {
                return;
            }

            $prospect->forceFill([
                'reference' => self::referenceFromId((int) $prospect->id),
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

    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'converted_client_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AccountingProspectContact::class);
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public function isConverted(): bool
    {
        return $this->converted_client_id !== null || $this->status === self::STATUS_WON;
    }

    public static function types(): array
    {
        return [
            self::TYPE_INDIVIDUAL,
            self::TYPE_COMPANY,
        ];
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_REFERRAL,
            self::SOURCE_WEBSITE,
            self::SOURCE_CALL,
            self::SOURCE_SOCIAL,
            self::SOURCE_EVENT,
            self::SOURCE_CAMPAIGN,
            self::SOURCE_OTHER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_QUALIFIED,
            self::STATUS_PROPOSAL_SENT,
            self::STATUS_WON,
            self::STATUS_LOST,
        ];
    }

    public static function interestLevels(): array
    {
        return [
            self::INTEREST_COLD,
            self::INTEREST_WARM,
            self::INTEREST_HOT,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'PRS-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
