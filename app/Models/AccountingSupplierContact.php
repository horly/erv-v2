<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSupplierContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'position',
        'department',
        'email',
        'phone',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(AccountingSupplier::class, 'accounting_supplier_id');
    }
}
