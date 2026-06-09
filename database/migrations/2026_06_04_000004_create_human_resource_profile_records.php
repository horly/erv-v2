<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('human_resource_profile_records')) {
            return;
        }

        Schema::create('human_resource_profile_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
            $table->unsignedBigInteger('human_resource_employee_id')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_type', 40);
            $table->string('reference', 40)->unique();
            $table->string('title');
            $table->string('category', 80)->nullable();
            $table->string('status', 40)->default('active');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('human_resource_employee_id', 'hr_profile_employee_fk')->references('id')->on('human_resource_employees')->nullOnDelete();
            $table->index(['company_site_id', 'record_type', 'status'], 'hr_profile_site_type_status_idx');
            $table->index(['date_from', 'date_to'], 'hr_profile_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_resource_profile_records');
    }
};
