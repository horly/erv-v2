<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTaskActivity extends Model
{
    use HasFactory;

    public const TYPE_CREATED = 'created';
    public const TYPE_UPDATED = 'updated';
    public const TYPE_COMPLETED = 'completed';
    public const TYPE_CANCELLED = 'cancelled';
    public const TYPE_AUTOMATIC_CREATED = 'automatic_created';

    protected $fillable = [
        'accounting_task_id',
        'created_by',
        'action_type',
        'from_status',
        'to_status',
        'notes',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AccountingTask::class, 'accounting_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
