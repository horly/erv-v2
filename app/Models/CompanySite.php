<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanySite extends Model
{
    use HasFactory;

    public const TYPE_PRODUCTION = 'production';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_OFFICE = 'office';
    public const TYPE_SHOP = 'shop';
    public const TYPE_ARCHIVE = 'archive';
    public const TYPE_OTHER = 'other';

    public const MODULE_ACCOUNTING = 'accounting';
    public const MODULE_HUMAN_RESOURCES = 'human_resources';
    public const MODULE_ARCHIVING = 'archiving';
    public const MODULE_DOCUMENT_MANAGEMENT = 'document_management';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'responsible_id',
        'name',
        'type',
        'code',
        'city',
        'phone',
        'email',
        'address',
        'modules',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_site_user')
            ->withPivot(['module_permissions', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    public static function types(): array
    {
        return [
            self::TYPE_PRODUCTION,
            self::TYPE_WAREHOUSE,
            self::TYPE_OFFICE,
            self::TYPE_SHOP,
            self::TYPE_ARCHIVE,
            self::TYPE_OTHER,
        ];
    }

    public static function modules(): array
    {
        return [
            self::MODULE_ACCOUNTING,
            self::MODULE_HUMAN_RESOURCES,
            self::MODULE_ARCHIVING,
            self::MODULE_DOCUMENT_MANAGEMENT,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }
}
