<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_site_id',
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'from_status',
        'to_status',
        'comment',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
