<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingDeliveryNoteSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_line_id',
        'serial_number',
        'position',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(AccountingDeliveryNoteLine::class, 'delivery_note_line_id');
    }
}
