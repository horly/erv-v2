<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_site_id',
        'actor_id',
        'action_key',
        'module_key',
        'subject_type',
        'subject_id',
        'subject_reference',
        'icon',
        'title',
        'message',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AccountingNotificationRead::class);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads->contains(fn (AccountingNotificationRead $read): bool => (int) $read->user_id === (int) $user->id && $read->read_at !== null);
    }

    public function markReadBy(User $user): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()],
        );
    }
}
