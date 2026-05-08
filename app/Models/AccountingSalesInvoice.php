<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingSalesInvoice extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'FAC';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const TITLE_CASH_REGISTER = '__cash_register_sale__';

    protected $fillable = [
        'company_site_id',
        'cash_register_session_id',
        'client_id',
        'customer_order_id',
        'delivery_note_id',
        'proforma_invoice_id',
        'created_by',
        'reference',
        'title',
        'invoice_date',
        'due_date',
        'currency',
        'status',
        'payment_terms',
        'subtotal',
        'discount_total',
        'total_ht',
        'tax_rate',
        'tax_amount',
        'total_ttc',
        'paid_total',
        'balance_due',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(AccountingCashRegisterSession::class, 'cash_register_session_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'client_id');
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(AccountingCustomerOrder::class, 'customer_order_id');
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(AccountingDeliveryNote::class, 'delivery_note_id');
    }

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(AccountingProformaInvoice::class, 'proforma_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoiceLine::class, 'sales_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoicePayment::class, 'sales_invoice_id');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ISSUED,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }
}
