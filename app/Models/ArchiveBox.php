<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveBox extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'BOI';

    protected $fillable = ['company_site_id', 'archive_shelf_id', 'archive_compartment_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    protected $appends = ['physical_path'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(ArchiveShelf::class, 'archive_shelf_id');
    }

    public function compartment(): BelongsTo
    {
        return $this->belongsTo(ArchiveCompartment::class, 'archive_compartment_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(ArchiveContainer::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ArchiveRecord::class);
    }

    public function getPhysicalPathAttribute(): string
    {
        $shelf = $this->compartment?->shelf ?? $this->shelf;
        $cabinet = $shelf?->cabinet;
        $rack = $cabinet?->rack;
        $room = $rack?->room;

        return collect([
            $room?->name,
            $rack?->name,
            $cabinet?->name,
            $shelf?->name,
            $this->compartment?->name,
            $this->name,
        ])->filter()->implode(' / ');
    }
}
