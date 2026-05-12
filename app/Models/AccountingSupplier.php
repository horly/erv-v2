<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingSupplier extends Model
{
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_COMPANY = 'company';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_site_id',
        'created_by',
        'reference',
        'type',
        'name',
        'profession',
        'phone',
        'email',
        'address',
        'rccm',
        'id_nat',
        'nif',
        'bank_name',
        'account_number',
        'currency',
        'website',
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (AccountingSupplier $supplier): void {
            if (filled($supplier->reference)) {
                return;
            }

            $supplier->forceFill([
                'reference' => self::referenceFromId((int) $supplier->id),
            ])->saveQuietly();
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AccountingSupplierContact::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(AccountingPurchase::class, 'supplier_id');
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public static function types(): array
    {
        return [
            self::TYPE_INDIVIDUAL,
            self::TYPE_COMPANY,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'FRS-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
