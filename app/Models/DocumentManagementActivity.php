<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentManagementActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_management_record_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'comment',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementRecord::class, 'document_management_record_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
