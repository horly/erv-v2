<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingTreasuryMovement extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'TRS';

    public const DIRECTION_INFLOW = 'inflow';
    public const DIRECTION_OUTFLOW = 'outflow';

    public const TYPE_SALES_PAYMENT = 'sales_payment';
    public const TYPE_OTHER_INCOME = 'other_income';
    public const TYPE_RECEIVABLE_PAYMENT = 'receivable_payment';
    public const TYPE_PURCHASE_PAYMENT = 'purchase_payment';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_DEBT_PAYMENT = 'debt_payment';
    public const TYPE_BANK_ADJUSTMENT = 'bank_adjustment';

    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_site_id',
        'payment_method_id',
        'created_by',
        'reference',
        'movement_type',
        'source_type',
        'source_id',
        'source_reference',
        'direction',
        'label',
        'description',
        'amount',
        'currency',
        'movement_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'movement_date' => 'date',
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

    public function reconciliationMatches(): HasMany
    {
        return $this->hasMany(AccountingBankReconciliationMatch::class, 'treasury_movement_id');
    }

    public static function directions(): array
    {
        return [self::DIRECTION_INFLOW, self::DIRECTION_OUTFLOW];
    }

    public static function types(): array
    {
        return [
            self::TYPE_SALES_PAYMENT,
            self::TYPE_OTHER_INCOME,
            self::TYPE_RECEIVABLE_PAYMENT,
            self::TYPE_PURCHASE_PAYMENT,
            self::TYPE_EXPENSE,
            self::TYPE_DEBT_PAYMENT,
            self::TYPE_BANK_ADJUSTMENT,
        ];
    }
}
