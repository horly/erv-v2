<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveRecord extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ARC';

    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_REVIEW = 'review';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DESTROYED = 'destroyed';
    public const STATUS_MISTAKEN = 'mistaken';

    protected $fillable = [
        'company_site_id',
        'archive_location_id',
        'archive_container_id',
        'archive_box_id',
        'created_by',
        'reference',
        'title',
        'document_type',
        'category',
        'owner_service',
        'document_date',
        'archived_at',
        'retention_until',
        'confidentiality_level',
        'status',
        'file_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'archived_at' => 'date',
            'retention_until' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ArchiveLocation::class, 'archive_location_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ArchiveContainer::class, 'archive_container_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class, 'archive_box_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ArchiveMovement::class);
    }
}
