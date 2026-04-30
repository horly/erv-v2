<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingClientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'accounting_client_id',
        'full_name',
        'position',
        'department',
        'email',
        'phone',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(AccountingClient::class, 'accounting_client_id');
    }
}
