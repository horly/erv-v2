<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPaymentMethod extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'PAY';
    public const TYPE_CASH = 'cash';
    public const TYPE_BANK = 'bank';
    public const TYPE_MOBILE_MONEY = 'mobile_money';
    public const TYPE_CARD = 'card';
    public const TYPE_CHECK = 'check';
    public const TYPE_CUSTOMER_CREDIT = 'customer_credit';
    public const TYPE_OTHER = 'other';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'name',
        'type',
        'currency_code',
        'code',
        'bank_name',
        'account_holder',
        'account_number',
        'iban',
        'bic_swift',
        'bank_address',
        'description',
        'is_default',
        'is_system_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
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

    public function salesInvoicePayments(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoicePayment::class, 'payment_method_id');
    }

    public static function types(): array
    {
        return [
            self::TYPE_CASH,
            self::TYPE_BANK,
            self::TYPE_MOBILE_MONEY,
            self::TYPE_CARD,
            self::TYPE_CHECK,
            self::TYPE_CUSTOMER_CREDIT,
            self::TYPE_OTHER,
        ];
    }
}
