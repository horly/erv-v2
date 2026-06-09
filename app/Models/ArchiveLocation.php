<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveLocation extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'LOC';

    public const TYPE_ROOM = 'room';
    public const TYPE_ZONE = 'zone';
    public const TYPE_CABINET = 'cabinet';
    public const TYPE_SHELF = 'shelf';
    public const TYPE_COMPARTMENT = 'compartment';
    public const TYPE_BOX = 'box';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULL = 'full';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'parent_id',
        'created_by',
        'reference',
        'type',
        'name',
        'code',
        'capacity',
        'status',
        'description',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('type')->orderBy('name');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(ArchiveContainer::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ArchiveRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
