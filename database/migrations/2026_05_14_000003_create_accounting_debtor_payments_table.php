<?php

use App\Models\AccountingDebtor;
use App\Models\AccountingPaymentMethod;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_debtor_payments')) {
            $indexes = collect(DB::select('SHOW INDEX FROM accounting_debtor_payments'))->pluck('Key_name')->all();

            Schema::table('accounting_debtor_payments', function (Blueprint $table) use ($indexes): void {
                if (! in_array('adp_debtor_date_idx', $indexes, true)) {
                    $table->index(['debtor_id', 'payment_date'], 'adp_debtor_date_idx');
                }

                if (! in_array('adp_method_date_idx', $indexes, true)) {
                    $table->index(['payment_method_id', 'payment_date'], 'adp_method_date_idx');
                }
            });

            return;
        }

        Schema::create('accounting_debtor_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(AccountingDebtor::class, 'debtor_id')->constrained('accounting_debtors')->cascadeOnDelete();
            $table->foreignIdFor(AccountingPaymentMethod::class, 'payment_method_id')->constrained('accounting_payment_methods')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['debtor_id', 'payment_date'], 'adp_debtor_date_idx');
            $table->index(['payment_method_id', 'payment_date'], 'adp_method_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_debtor_payments');
    }
};
