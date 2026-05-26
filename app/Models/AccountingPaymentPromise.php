<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPaymentPromise extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_HONORED = 'honored';
    public const STATUS_BROKEN = 'broken';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_reminder_id',
        'created_by',
        'amount',
        'currency',
        'promised_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'promised_date' => 'date',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(AccountingPaymentReminder::class, 'payment_reminder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
