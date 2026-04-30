<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingDebtor extends Model
{
    use HasFactory;

    public const TYPE_CLIENT = 'client';
    public const TYPE_EMPLOYEE = 'employee';
    public const TYPE_PARTNER = 'partner';
    public const TYPE_ASSOCIATE = 'associate';
    public const TYPE_ADVANCE = 'advance';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'type',
        'name',
        'phone',
        'email',
        'address',
        'currency',
        'initial_amount',
        'received_amount',
        'due_date',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'initial_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AccountingDebtor $debtor): void {
            if (filled($debtor->reference)) {
                return;
            }

            $debtor->forceFill([
                'reference' => self::referenceFromId((int) $debtor->id),
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

    public function balanceReceivable(): float
    {
        return max(0, (float) $this->initial_amount - (float) $this->received_amount);
    }

    public static function types(): array
    {
        return [
            self::TYPE_CLIENT,
            self::TYPE_EMPLOYEE,
            self::TYPE_PARTNER,
            self::TYPE_ASSOCIATE,
            self::TYPE_ADVANCE,
            self::TYPE_OTHER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SETTLED,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'DEB-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
