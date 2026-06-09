<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumanResourceAttendance extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_REMOTE = 'remote';
    public const STATUS_ON_LEAVE = 'on_leave';

    protected $fillable = [
        'human_resource_employee_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'worked_hours',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'worked_hours' => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HumanResourceEmployee::class, 'human_resource_employee_id');
    }
}
