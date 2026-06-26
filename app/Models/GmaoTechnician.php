<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmaoTechnician extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'human_resource_employee_id',
        'reference',
        'name',
        'specialty',
        'phone',
        'email',
        'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HumanResourceEmployee::class, 'human_resource_employee_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(GmaoWorkOrder::class);
    }
}
