<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingBankReconciliationMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'statement_line_id',
        'treasury_movement_id',
        'created_by',
        'amount',
        'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'matched_at' => 'datetime',
        ];
    }

    public function statementLine(): BelongsTo
    {
        return $this->belongsTo(AccountingBankStatementLine::class, 'statement_line_id');
    }

    public function treasuryMovement(): BelongsTo
    {
        return $this->belongsTo(AccountingTreasuryMovement::class, 'treasury_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
