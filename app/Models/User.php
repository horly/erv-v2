<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_USER,
        self::ROLE_SUPERADMIN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'name',
        'email',
        'password',
        'role',
        'address',
        'phone_number',
        'grade',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(CompanySite::class, 'company_site_user')
            ->withPivot(['module_permissions', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    public function responsibleSites(): HasMany
    {
        return $this->hasMany(CompanySite::class, 'responsible_id');
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        if (Str::startsWith($this->profile_photo_path, ['http://', 'https://'])) {
            return $this->profile_photo_path;
        }

        return Storage::disk('public')->exists($this->profile_photo_path)
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function canManageCompany(Company $company, string $permission): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if ($this->isAdmin()) {
            return $this->subscription_id !== null
                && $this->subscription_id === $company->subscription_id;
        }

        $allowedPermissions = ['can_view', 'can_create', 'can_update', 'can_delete'];

        if (! in_array($permission, $allowedPermissions, true)) {
            return false;
        }

        $assignedCompany = $this->companies()
            ->whereKey($company->getKey())
            ->first();

        return (bool) $assignedCompany?->pivot->{$permission};
    }

    public function redirectRouteAfterLogin(): string
    {
        return $this->isSuperadmin() ? 'admin.dashboard' : 'main';
    }
}
