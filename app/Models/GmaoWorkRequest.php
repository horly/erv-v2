<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GmaoWorkRequest extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'company_site_id',
        'gmao_equipment_id',
        'created_by',
        'reference',
        'title',
        'requester_name',
        'priority',
        'status',
        'description',
        'requested_at',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(GmaoEquipment::class, 'gmao_equipment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(GmaoWorkOrder::class);
    }
}
