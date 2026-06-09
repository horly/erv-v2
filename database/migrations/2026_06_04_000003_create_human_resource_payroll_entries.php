<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('human_resource_payroll_entries')) {
            return;
        }

        Schema::create('human_resource_payroll_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('human_resource_employee_id');
            $table->unsignedBigInteger('human_resource_contract_id')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 40)->unique();
            $table->date('period_month');
            $table->date('payment_date')->nullable();
            $table->string('status', 25)->default('draft');
            $table->string('currency', 3)->default('USD');
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['human_resource_employee_id', 'period_month'], 'hr_payroll_employee_period_unique');
            $table->index(['period_month', 'status'], 'hr_payroll_period_status_idx');
            $table->foreign('human_resource_employee_id', 'hr_payroll_employee_fk')->references('id')->on('human_resource_employees')->cascadeOnDelete();
            $table->foreign('human_resource_contract_id', 'hr_payroll_contract_fk')->references('id')->on('human_resource_contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_resource_payroll_entries');
    }
};
