<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_module_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pdf_primary_color', 7)->default('#2F70C8');
            $table->string('pdf_accent_color', 7)->default('#40AEF4');
            $table->string('pdf_tint_color', 7)->default('#D7EEF8');
            $table->boolean('pdf_show_qr_code')->default(true);
            $table->boolean('pdf_show_footer_branding')->default(true);
            $table->timestamps();
        });

        Schema::create('accounting_menu_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('menu_key', 64);
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->unique(['company_site_id', 'user_id', 'menu_key'], 'acct_menu_permissions_site_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_menu_permissions');
        Schema::dropIfExists('accounting_module_settings');
    }
};
