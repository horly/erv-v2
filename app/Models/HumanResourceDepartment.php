<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HumanResourceDepartment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'manager_user_id',
        'manager_employee_id',
        'code',
        'name',
        'description',
        'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(HumanResourceEmployee::class, 'manager_employee_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(HumanResourceEmployee::class);
    }
}
