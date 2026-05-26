<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingTask extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'TAC';

    public const TYPE_CALL = 'call';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_CONTROL = 'control';
    public const TYPE_ADMINISTRATIVE = 'administrative';
    public const TYPE_OTHER = 'other';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_SALES_INVOICE = 'sales_invoice';
    public const SOURCE_PAYMENT_PROMISE = 'payment_promise';
    public const SOURCE_PAYMENT_REMINDER = 'payment_reminder';

    protected $fillable = [
        'company_site_id',
        'assigned_to',
        'created_by',
        'completed_by',
        'client_id',
        'supplier_id',
        'reference',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'completion_notes',
        'source_type',
        'source_id',
        'source_reference',
        'source_label',
        'is_automatic',
        'automation_key',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'is_automatic' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'client_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(AccountingSupplier::class, 'supplier_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AccountingTaskActivity::class, 'accounting_task_id');
    }

    public static function types(): array
    {
        return [
            self::TYPE_CALL,
            self::TYPE_REMINDER,
            self::TYPE_PAYMENT,
            self::TYPE_DELIVERY,
            self::TYPE_CONTROL,
            self::TYPE_ADMINISTRATIVE,
            self::TYPE_OTHER,
        ];
    }

    public static function priorities(): array
    {
        return [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
    }

    public static function statuses(): array
    {
        return [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED];
    }
}
