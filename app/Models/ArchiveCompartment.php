<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveCompartment extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'CAS';

    protected $fillable = ['company_site_id', 'archive_shelf_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(ArchiveShelf::class, 'archive_shelf_id');
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(ArchiveBox::class);
    }
}
