<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingBankReconciliation extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'RAP';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_site_id',
        'payment_method_id',
        'created_by',
        'closed_by',
        'reference',
        'period_start',
        'period_end',
        'statement_opening_balance',
        'statement_closing_balance',
        'erp_closing_balance',
        'difference',
        'currency',
        'status',
        'notes',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'statement_opening_balance' => 'decimal:2',
            'statement_closing_balance' => 'decimal:2',
            'erp_closing_balance' => 'decimal:2',
            'difference' => 'decimal:2',
            'closed_at' => 'datetime',
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

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingBankStatementLine::class, 'bank_reconciliation_id');
    }
}
