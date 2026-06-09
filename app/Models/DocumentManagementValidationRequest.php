<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentManagementValidationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'document_management_record_id',
        'document_management_validation_circuit_id',
        'current_step_id',
        'requested_by',
        'status',
        'started_at',
        'completed_at',
        'due_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_at' => 'date',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementRecord::class, 'document_management_record_id');
    }

    public function circuit(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementValidationCircuit::class, 'document_management_validation_circuit_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementValidationStep::class, 'current_step_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationAction::class, 'document_management_validation_request_id')
            ->latest();
    }
}
