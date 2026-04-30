<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingProspectContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'position',
        'department',
        'email',
        'phone',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(AccountingProspect::class, 'accounting_prospect_id');
    }
}
