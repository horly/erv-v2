<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentManagementRecord extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'GED';

    public const TYPE_INCOMING = 'incoming';
    public const TYPE_OUTGOING = 'outgoing';
    public const TYPE_INTERNAL = 'internal';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_SENT = 'sent';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'company_site_id',
        'document_management_folder_id',
        'created_by',
        'assigned_to',
        'reference',
        'record_type',
        'direction',
        'subject',
        'sender',
        'recipient',
        'category',
        'priority',
        'status',
        'received_at',
        'sent_at',
        'due_at',
        'closed_at',
        'file_path',
        'summary',
        'decision',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'sent_at' => 'date',
            'due_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentManagementFolder::class, 'document_management_folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DocumentManagementActivity::class)->latest();
    }

    public function validationRequests(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationRequest::class, 'document_management_record_id')->latest();
    }
}
