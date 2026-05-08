<?php

use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_cash_register_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'closure_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->decimal('opening_float', 18, 2)->default(0);
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('expected_cash_amount', 18, 2)->default(0);
            $table->decimal('expected_other_amount', 18, 2)->default(0);
            $table->decimal('expected_total_amount', 18, 2)->default(0);
            $table->decimal('counted_cash_amount', 18, 2)->nullable();
            $table->decimal('counted_other_amount', 18, 2)->nullable();
            $table->decimal('counted_total_amount', 18, 2)->nullable();
            $table->decimal('difference_amount', 18, 2)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_cash_session_site_status_idx');
            $table->index(['company_site_id', 'opened_at'], 'acct_cash_session_site_opened_idx');
        });

        Schema::table('accounting_sales_invoices', function (Blueprint $table): void {
            $table->foreignId('cash_register_session_id')
                ->nullable()
                ->after('company_site_id')
                ->constrained('accounting_cash_register_sessions')
                ->nullOnDelete();

            $table->index('cash_register_session_id', 'acct_sales_invoice_cash_session_idx');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_sales_invoices', function (Blueprint $table): void {
            $table->dropForeign(['cash_register_session_id']);
            $table->dropIndex('acct_sales_invoice_cash_session_idx');
            $table->dropColumn('cash_register_session_id');
        });

        Schema::dropIfExists('accounting_cash_register_sessions');
    }
};
