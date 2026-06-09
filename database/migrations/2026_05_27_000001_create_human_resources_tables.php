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
        Schema::create('human_resource_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_site_id', 'code'], 'hr_departments_site_code_unique');
            $table->index(['company_site_id', 'status'], 'hr_departments_site_status_idx');
        });

        Schema::create('human_resource_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('human_resource_department_id')->nullable()->constrained('human_resource_departments')->nullOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_number', 30)->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('professional_email')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type', 30)->default('full_time');
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status', 25)->default('active');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'hr_employees_site_status_idx');
            $table->index(['company_site_id', 'human_resource_department_id'], 'hr_employees_site_department_idx');
            $table->index(['company_site_id', 'hire_date'], 'hr_employees_site_hire_date_idx');
        });

        Schema::create('human_resource_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('human_resource_employee_id')->constrained('human_resource_employees')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('type', 30)->default('permanent');
            $table->string('status', 25)->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('monthly_salary', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['human_resource_employee_id', 'status'], 'hr_contracts_employee_status_idx');
            $table->index(['status', 'end_date'], 'hr_contracts_status_end_idx');
        });

        Schema::create('human_resource_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('human_resource_employee_id')->constrained('human_resource_employees')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('type', 30)->default('annual');
            $table->string('status', 25)->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 8, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['human_resource_employee_id', 'status'], 'hr_leaves_employee_status_idx');
            $table->index(['status', 'start_date'], 'hr_leaves_status_start_idx');
        });

        Schema::create('human_resource_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('human_resource_employee_id')->constrained('human_resource_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('check_in_at')->nullable();
            $table->time('check_out_at')->nullable();
            $table->decimal('worked_hours', 6, 2)->default(0);
            $table->string('status', 25)->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['human_resource_employee_id', 'work_date'], 'hr_attendances_employee_date_unique');
            $table->index(['work_date', 'status'], 'hr_attendances_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_resource_attendances');
        Schema::dropIfExists('human_resource_leave_requests');
        Schema::dropIfExists('human_resource_contracts');
        Schema::dropIfExists('human_resource_employees');
        Schema::dropIfExists('human_resource_departments');
    }
};
