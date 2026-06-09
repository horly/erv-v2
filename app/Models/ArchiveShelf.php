<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveShelf extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'ETA';

    protected $fillable = ['company_site_id', 'archive_cabinet_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(ArchiveCabinet::class, 'archive_cabinet_id');
    }

    public function compartments(): HasMany
    {
        return $this->hasMany(ArchiveCompartment::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(ArchiveBox::class);
    }
}
