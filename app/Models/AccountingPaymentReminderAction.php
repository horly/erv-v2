<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPaymentReminderAction extends Model
{
    use HasFactory;

    public const TYPE_REMINDER_SENT = 'reminder_sent';
    public const TYPE_PROMISE = 'promise';
    public const TYPE_DISPUTED = 'disputed';
    public const TYPE_SUSPENDED = 'suspended';
    public const TYPE_SETTLED = 'settled';

    protected $fillable = [
        'payment_reminder_id',
        'created_by',
        'action_type',
        'channel',
        'subject',
        'message',
        'next_reminder_date',
        'action_at',
    ];

    protected function casts(): array
    {
        return [
            'next_reminder_date' => 'date',
            'action_at' => 'datetime',
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
