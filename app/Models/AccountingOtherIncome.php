<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingOtherIncome extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ENT';
    public const TYPE_OWNER_CONTRIBUTION = 'owner_contribution';
    public const TYPE_SUBSIDY = 'subsidy';
    public const TYPE_REFUND = 'refund';
    public const TYPE_EXCEPTIONAL_INCOME = 'exceptional_income';
    public const TYPE_BANK_INTEREST = 'bank_interest';
    public const TYPE_POSITIVE_ADJUSTMENT = 'positive_adjustment';
    public const TYPE_MISCELLANEOUS = 'miscellaneous';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_site_id',
        'payment_method_id',
        'created_by',
        'reference',
        'income_date',
        'type',
        'label',
        'description',
        'amount',
        'currency',
        'payment_reference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'payment_method_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public static function types(): array
    {
        return [
            self::TYPE_OWNER_CONTRIBUTION,
            self::TYPE_SUBSIDY,
            self::TYPE_REFUND,
            self::TYPE_EXCEPTIONAL_INCOME,
            self::TYPE_BANK_INTEREST,
            self::TYPE_POSITIVE_ADJUSTMENT,
            self::TYPE_MISCELLANEOUS,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_VALIDATED,
            self::STATUS_CANCELLED,
        ];
    }
}
