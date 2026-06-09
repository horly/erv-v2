<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveRack extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'RAY';

    protected $fillable = ['company_site_id', 'archive_room_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ArchiveRoom::class, 'archive_room_id');
    }

    public function cabinets(): HasMany
    {
        return $this->hasMany(ArchiveCabinet::class);
    }
}
