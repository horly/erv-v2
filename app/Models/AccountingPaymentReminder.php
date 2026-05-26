<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPaymentReminder extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'REL';

    public const LEVEL_FRIENDLY = 'friendly';
    public const LEVEL_FIRST = 'first';
    public const LEVEL_SECOND = 'second';
    public const LEVEL_FORMAL_NOTICE = 'formal_notice';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_LETTER = 'letter';
    public const CHANNEL_OTHER = 'other';

    public const STATUS_SENT = 'sent';
    public const STATUS_PROMISE = 'promise';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'company_site_id',
        'sales_invoice_id',
        'debtor_id',
        'client_id',
        'created_by',
        'reference',
        'level',
        'channel',
        'status',
        'subject',
        'message',
        'next_reminder_date',
        'notes',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'next_reminder_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(AccountingSalesInvoice::class, 'sales_invoice_id');
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(AccountingDebtor::class, 'debtor_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'client_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AccountingPaymentReminderAction::class, 'payment_reminder_id');
    }

    public function promises(): HasMany
    {
        return $this->hasMany(AccountingPaymentPromise::class, 'payment_reminder_id');
    }

    public static function levels(): array
    {
        return [self::LEVEL_FRIENDLY, self::LEVEL_FIRST, self::LEVEL_SECOND, self::LEVEL_FORMAL_NOTICE];
    }

    public static function channels(): array
    {
        return [self::CHANNEL_EMAIL, self::CHANNEL_PHONE, self::CHANNEL_LETTER, self::CHANNEL_OTHER];
    }

    public static function statuses(): array
    {
        return [self::STATUS_SENT, self::STATUS_PROMISE, self::STATUS_SETTLED, self::STATUS_DISPUTED, self::STATUS_SUSPENDED];
    }
}
