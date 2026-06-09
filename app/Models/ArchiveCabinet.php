<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveCabinet extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ARM';

    protected $fillable = ['company_site_id', 'archive_rack_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(ArchiveRack::class, 'archive_rack_id');
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(ArchiveShelf::class);
    }
}
