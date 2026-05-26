<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingBankStatementLine extends Model
{
    use HasFactory;

    public const DIRECTION_INFLOW = 'inflow';
    public const DIRECTION_OUTFLOW = 'outflow';

    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'bank_reconciliation_id',
        'created_by',
        'transaction_date',
        'value_date',
        'bank_reference',
        'description',
        'direction',
        'amount',
        'status',
        'import_batch',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(AccountingBankReconciliation::class, 'bank_reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(AccountingBankReconciliationMatch::class, 'statement_line_id');
    }
}
