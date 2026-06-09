<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveContainer extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CLS';

    public const CONFIDENTIALITY_PUBLIC = 'public';
    public const CONFIDENTIALITY_INTERNAL = 'internal';
    public const CONFIDENTIALITY_CONFIDENTIAL = 'confidential';
    public const CONFIDENTIALITY_SECRET = 'secret';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_SEALED = 'sealed';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_DESTROYED = 'destroyed';

    protected $fillable = [
        'company_site_id',
        'archive_location_id',
        'archive_box_id',
        'created_by',
        'reference',
        'title',
        'category',
        'owner_service',
        'period_label',
        'confidentiality_level',
        'capacity',
        'status',
        'description',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ArchiveLocation::class, 'archive_location_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class, 'archive_box_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ArchiveRecord::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ArchiveMovement::class);
    }
}
