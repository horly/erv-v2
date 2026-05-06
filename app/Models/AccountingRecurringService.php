<?php

namespace App\Models;

use App\Models\Concerns\HasAccountingReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingRecurringService extends Model
{
    use HasAccountingReference;
    use HasFactory;

    public const REFERENCE_PREFIX = 'REC';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'service_id',
        'created_by',
        'reference',
        'name',
        'frequency',
        'start_date',
        'next_invoice_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'next_invoice_date' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AccountingService::class, 'service_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function frequencies(): array
    {
        return [self::FREQUENCY_MONTHLY, self::FREQUENCY_QUARTERLY, self::FREQUENCY_YEARLY];
    }
}
