<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentManagementValidationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_management_validation_request_id',
        'document_management_validation_step_id',
        'actor_id',
        'action',
        'status',
        'comment',
    ];

    public function validationRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementValidationRequest::class, 'document_management_validation_request_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementValidationStep::class, 'document_management_validation_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
