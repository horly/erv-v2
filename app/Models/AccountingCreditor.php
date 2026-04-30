<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingCreditor extends Model
{
    use HasFactory;

    public const TYPE_SUPPLIER = 'supplier';
    public const TYPE_BANK = 'bank';
    public const TYPE_LANDLORD = 'landlord';
    public const TYPE_EMPLOYEE = 'employee';
    public const TYPE_TAX = 'tax';
    public const TYPE_PARTNER = 'partner';
    public const TYPE_LENDER = 'lender';
    public const TYPE_OTHER = 'other';

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

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
        'paid_amount',
        'due_date',
        'description',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'initial_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AccountingCreditor $creditor): void {
            if (filled($creditor->reference)) {
                return;
            }

            $creditor->forceFill([
                'reference' => self::referenceFromId((int) $creditor->id),
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

    public function balanceDue(): float
    {
        return max(0, (float) $this->initial_amount - (float) $this->paid_amount);
    }

    public static function types(): array
    {
        return [
            self::TYPE_SUPPLIER,
            self::TYPE_BANK,
            self::TYPE_LANDLORD,
            self::TYPE_EMPLOYEE,
            self::TYPE_TAX,
            self::TYPE_PARTNER,
            self::TYPE_LENDER,
            self::TYPE_OTHER,
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
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
        return 'CRE-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
