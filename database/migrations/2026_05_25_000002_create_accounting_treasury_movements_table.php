<?php

use App\Models\AccountingPaymentMethod;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_treasury_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingPaymentMethod::class, 'payment_method_id')->nullable()->constrained('accounting_payment_methods')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 40);
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('source_reference')->nullable();
            $table->string('direction', 20);
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('movement_date');
            $table->string('status', 30)->default('validated');
            $table->timestamps();

            $table->unique(['company_site_id', 'source_type', 'source_id'], 'acct_treasury_unique_source');
            $table->index(['company_site_id', 'movement_date'], 'acct_treasury_site_date_idx');
            $table->index(['company_site_id', 'currency', 'status'], 'acct_treasury_site_currency_status_idx');
            $table->index(['payment_method_id', 'status'], 'acct_treasury_method_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_treasury_movements');
    }
};
