<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'status',
        'company_limit',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at === null
            || $this->expires_at->isToday()
            || $this->expires_at->isFuture();
    }

    public static function statusForExpiration(?string $expiresAt): string
    {
        return $expiresAt !== null && $expiresAt < now()->toDateString()
            ? 'expired'
            : 'active';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
