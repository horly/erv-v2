<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'created_by',
        'name',
        'rccm',
        'id_nat',
        'nif',
        'website',
        'slogan',
        'country',
        'email',
        'logo',
        'phone_number',
        'address',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        if (Str::startsWith($this->logo, ['http://', 'https://'])) {
            return $this->logo;
        }

        return Storage::disk('public')->exists($this->logo)
            ? Storage::disk('public')->url($this->logo)
            : null;
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function phones(): HasMany
    {
        return $this->hasMany(CompanyPhone::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CompanyAccount::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(CompanySite::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }
}
