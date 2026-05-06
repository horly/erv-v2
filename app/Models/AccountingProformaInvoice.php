<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingProformaInvoice extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'PRO';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONVERTED = 'converted';
    public const PAYMENT_FULL_ORDER = 'full_order';
    public const PAYMENT_HALF_ORDER = 'half_order';
    public const PAYMENT_THIRTY_ORDER = 'thirty_order';
    public const PAYMENT_ON_DELIVERY = 'on_delivery';
    public const PAYMENT_AFTER_DELIVERY = 'after_delivery';
    public const PAYMENT_TO_DISCUSS = 'to_discuss';

    protected $fillable = [
        'company_site_id',
        'client_id',
        'created_by',
        'reference',
        'title',
        'issue_date',
        'expiration_date',
        'currency',
        'status',
        'payment_terms',
        'subtotal',
        'discount_total',
        'total_ht',
        'tax_rate',
        'tax_amount',
        'total_ttc',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiration_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'client_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingProformaInvoiceLine::class, 'proforma_invoice_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
            self::STATUS_CONVERTED,
        ];
    }

    public static function paymentTerms(): array
    {
        return [
            self::PAYMENT_FULL_ORDER,
            self::PAYMENT_HALF_ORDER,
            self::PAYMENT_THIRTY_ORDER,
            self::PAYMENT_ON_DELIVERY,
            self::PAYMENT_AFTER_DELIVERY,
            self::PAYMENT_TO_DISCUSS,
        ];
    }
}
