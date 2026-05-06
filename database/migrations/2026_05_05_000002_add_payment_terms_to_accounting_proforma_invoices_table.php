<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_proforma_invoices', function (Blueprint $table): void {
            $table->string('payment_terms', 40)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_proforma_invoices', function (Blueprint $table): void {
            $table->dropColumn('payment_terms');
        });
    }
};
