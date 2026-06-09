<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentManagementValidationStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_management_validation_circuit_id',
        'validator_id',
        'step_order',
        'name',
        'role_name',
        'due_days',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'due_days' => 'integer',
        ];
    }

    public function circuit(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementValidationCircuit::class, 'document_management_validation_circuit_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function validationActions(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationAction::class, 'document_management_validation_step_id');
    }
}
