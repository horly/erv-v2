<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveRoom extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'SAL';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULL = 'full';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = ['company_site_id', 'created_by', 'reference', 'name', 'code', 'capacity', 'status', 'description'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function racks(): HasMany
    {
        return $this->hasMany(ArchiveRack::class);
    }
}
