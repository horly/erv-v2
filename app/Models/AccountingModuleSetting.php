<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingModuleSetting extends Model
{
    protected $fillable = [
        'company_site_id',
        'pdf_primary_color',
        'pdf_accent_color',
        'pdf_tint_color',
        'pdf_show_qr_code',
        'pdf_show_footer_branding',
    ];

    protected function casts(): array
    {
        return [
            'pdf_show_qr_code' => 'boolean',
            'pdf_show_footer_branding' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public static function defaults(): array
    {
        return [
            'pdf_primary_color' => '#2F70C8',
            'pdf_accent_color' => '#40AEF4',
            'pdf_tint_color' => '#D7EEF8',
            'pdf_show_qr_code' => true,
            'pdf_show_footer_branding' => true,
        ];
    }
}
