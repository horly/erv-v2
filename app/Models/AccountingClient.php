<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingClient extends Model
{
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_COMPANY = 'company';
    public const WALK_IN_NAMES = [
        'Client comptoir',
        'Client comptoire',
        'Client de passage',
        'Walk-in customer',
        'Counter customer',
    ];

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
    ];

    protected static function booted(): void
    {
        static::created(function (AccountingClient $client): void {
            if (filled($client->reference)) {
                return;
            }

            $client->forceFill([
                'reference' => self::referenceFromId((int) $client->id),
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
        return $this->hasMany(AccountingClientContact::class);
    }

    public function proformaInvoices(): HasMany
    {
        return $this->hasMany(AccountingProformaInvoice::class, 'client_id');
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(AccountingCustomerOrder::class, 'client_id');
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(AccountingDeliveryNote::class, 'client_id');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoice::class, 'client_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(AccountingCreditNote::class, 'client_id');
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public function isWalkInCustomer(): bool
    {
        return in_array(trim((string) $this->name), self::WALK_IN_NAMES, true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->isWalkInCustomer() ? __('main.walk_in_customer') : (string) $this->name;
    }

    public static function walkInCustomerNames(): array
    {
        return self::WALK_IN_NAMES;
    }

    public static function types(): array
    {
        return [
            self::TYPE_INDIVIDUAL,
            self::TYPE_COMPANY,
        ];
    }

    public static function referenceFromId(int $id): string
    {
        return 'CLT-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
