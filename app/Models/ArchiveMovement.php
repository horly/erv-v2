<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveMovement extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'MVT';

    protected $fillable = [
        'company_site_id',
        'archive_record_id',
        'archive_container_id',
        'from_location_id',
        'to_location_id',
        'from_archive_box_id',
        'to_archive_box_id',
        'actor_id',
        'reference',
        'moved_at',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(ArchiveRecord::class, 'archive_record_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ArchiveContainer::class, 'archive_container_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(ArchiveLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(ArchiveLocation::class, 'to_location_id');
    }

    public function fromBox(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class, 'from_archive_box_id');
    }

    public function toBox(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class, 'to_archive_box_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
