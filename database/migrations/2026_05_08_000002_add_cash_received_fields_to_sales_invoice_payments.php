<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_sales_invoice_payments', function (Blueprint $table): void {
            $table->decimal('amount_received', 18, 2)->nullable()->after('amount');
            $table->decimal('change_due', 18, 2)->nullable()->after('amount_received');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_sales_invoice_payments', function (Blueprint $table): void {
            $table->dropColumn(['amount_received', 'change_due']);
        });
    }
};
