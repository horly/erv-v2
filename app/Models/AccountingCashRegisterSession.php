<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCashRegisterSession extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CAI';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_site_id',
        'opened_by',
        'closed_by',
        'closure_validated_by',
        'reference',
        'status',
        'opening_float',
        'opened_at',
        'closed_at',
        'expected_cash_amount',
        'expected_other_amount',
        'expected_total_amount',
        'counted_cash_amount',
        'counted_other_amount',
        'counted_total_amount',
        'difference_amount',
        'opening_notes',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_float' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'expected_cash_amount' => 'decimal:2',
            'expected_other_amount' => 'decimal:2',
            'expected_total_amount' => 'decimal:2',
            'counted_cash_amount' => 'decimal:2',
            'counted_other_amount' => 'decimal:2',
            'counted_total_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_validated_by');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoice::class, 'cash_register_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
