<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSalesInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'payment_method_id',
        'received_by',
        'payment_date',
        'amount',
        'amount_received',
        'change_due',
        'currency',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(AccountingSalesInvoice::class, 'sales_invoice_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'payment_method_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
