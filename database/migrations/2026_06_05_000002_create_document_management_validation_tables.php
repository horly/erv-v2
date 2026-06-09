<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_management_validation_circuits')) {
            Schema::create('document_management_validation_circuits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('document_type', 40)->default('all');
                $table->string('service_owner', 120)->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'status'], 'ged_validation_circuits_site_status_idx');
            });
        }

        if (! Schema::hasTable('document_management_validation_steps')) {
            Schema::create('document_management_validation_steps', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_management_validation_circuit_id');
                $table->foreignIdFor(User::class, 'validator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('step_order');
                $table->string('name');
                $table->string('role_name', 120)->nullable();
                $table->unsignedSmallInteger('due_days')->nullable();
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->foreign('document_management_validation_circuit_id', 'ged_val_steps_circuit_fk')->references('id')->on('document_management_validation_circuits')->cascadeOnDelete();
                $table->unique(['document_management_validation_circuit_id', 'step_order'], 'ged_validation_steps_order_unique');
            });
        }

        if (! Schema::hasTable('document_management_validation_requests')) {
            Schema::create('document_management_validation_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_management_record_id');
                $table->unsignedBigInteger('document_management_validation_circuit_id');
                $table->unsignedBigInteger('current_step_id')->nullable();
                $table->foreignIdFor(User::class, 'requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 40)->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->date('due_at')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->foreign('document_management_record_id', 'ged_val_requests_record_fk')->references('id')->on('document_management_records')->cascadeOnDelete();
                $table->foreign('document_management_validation_circuit_id', 'ged_val_requests_circuit_fk')->references('id')->on('document_management_validation_circuits')->cascadeOnDelete();
                $table->foreign('current_step_id', 'ged_validation_requests_step_fk')->references('id')->on('document_management_validation_steps')->nullOnDelete();
                $table->index(['document_management_record_id', 'status'], 'ged_validation_requests_record_status_idx');
                $table->index(['document_management_validation_circuit_id', 'status'], 'ged_validation_requests_circuit_status_idx');
            });
        }

        if (! Schema::hasTable('document_management_validation_actions')) {
            Schema::create('document_management_validation_actions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_management_validation_request_id');
                $table->unsignedBigInteger('document_management_validation_step_id')->nullable();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40);
                $table->string('status', 40)->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->foreign('document_management_validation_request_id', 'ged_val_actions_request_fk')->references('id')->on('document_management_validation_requests')->cascadeOnDelete();
                $table->foreign('document_management_validation_step_id', 'ged_val_actions_step_fk')->references('id')->on('document_management_validation_steps')->nullOnDelete();
                $table->index(['document_management_validation_request_id', 'created_at'], 'ged_validation_actions_request_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_management_validation_actions');
        Schema::dropIfExists('document_management_validation_requests');
        Schema::dropIfExists('document_management_validation_steps');
        Schema::dropIfExists('document_management_validation_circuits');
    }
};
