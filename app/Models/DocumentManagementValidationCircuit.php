<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentManagementValidationCircuit extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_ALL = 'all';
    public const TYPE_INCOMING = DocumentManagementRecord::TYPE_INCOMING;
    public const TYPE_OUTGOING = DocumentManagementRecord::TYPE_OUTGOING;
    public const TYPE_INTERNAL = DocumentManagementRecord::TYPE_INTERNAL;

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'name',
        'document_type',
        'service_owner',
        'status',
        'description',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationStep::class, 'document_management_validation_circuit_id')
            ->orderBy('step_order');
    }

    public function validationRequests(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationRequest::class, 'document_management_validation_circuit_id');
    }
}
