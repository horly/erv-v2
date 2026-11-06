<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_sales_invoices', function (Blueprint $table): void {
            $table->timestamp('tax_paid_at')->nullable()->after('tax_amount');
            $table->foreignIdFor(User::class, 'tax_paid_by')->nullable()->after('tax_paid_at')->constrained('users')->nullOnDelete();
            $table->string('tax_payment_reference')->nullable()->after('tax_paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_sales_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tax_paid_by');
            $table->dropColumn(['tax_paid_at', 'tax_payment_reference']);
        });
    }
};
