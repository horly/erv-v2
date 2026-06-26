<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gmao_locations')) {
            Schema::create('gmao_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->string('reference')->index();
                $table->string('name');
                $table->string('type')->default('room');
                $table->string('building')->nullable();
                $table->string('floor')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_equipment')) {
            Schema::create('gmao_equipment', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_location_id')->nullable()->constrained('gmao_locations')->nullOnDelete();
                $table->string('reference')->index();
                $table->string('name');
                $table->string('category');
                $table->string('criticality')->default('medium')->index();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number')->nullable();
                $table->date('commissioned_at')->nullable();
                $table->date('warranty_until')->nullable();
                $table->string('status')->default('operational')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_technicians')) {
            Schema::create('gmao_technicians', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('human_resource_employee_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reference')->index();
                $table->string('name');
                $table->string('specialty')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('status')->default('available')->index();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_work_requests')) {
            Schema::create('gmao_work_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_equipment_id')->nullable()->constrained('gmao_equipment')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference')->index();
                $table->string('title');
                $table->string('requester_name')->nullable();
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('new')->index();
                $table->text('description')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_work_orders')) {
            Schema::create('gmao_work_orders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_work_request_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('gmao_equipment_id')->nullable()->constrained('gmao_equipment')->nullOnDelete();
                $table->foreignId('gmao_technician_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference')->index();
                $table->string('title');
                $table->string('type')->default('corrective')->index();
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('planned')->index();
                $table->decimal('estimated_hours', 8, 2)->default(0);
                $table->decimal('actual_hours', 8, 2)->default(0);
                $table->timestamp('planned_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_spare_parts')) {
            Schema::create('gmao_spare_parts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->string('reference')->index();
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('unit')->default('piece');
                $table->decimal('stock_quantity', 10, 2)->default(0);
                $table->decimal('minimum_quantity', 10, 2)->default(0);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('active')->index();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_work_order_parts')) {
            Schema::create('gmao_work_order_parts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('gmao_work_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_spare_part_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('gmao_preventive_plans')) {
            Schema::create('gmao_preventive_plans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_equipment_id')->constrained('gmao_equipment')->cascadeOnDelete();
                $table->string('reference')->index();
                $table->string('title');
                $table->string('frequency')->default('monthly')->index();
                $table->date('last_done_at')->nullable();
                $table->date('next_due_at')->nullable()->index();
                $table->string('status')->default('active')->index();
                $table->text('instructions')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_intervention_reports')) {
            Schema::create('gmao_intervention_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_work_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_technician_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reference')->index();
                $table->text('diagnosis')->nullable();
                $table->text('work_done')->nullable();
                $table->text('recommendations')->nullable();
                $table->string('result')->default('resolved')->index();
                $table->timestamp('reported_at')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_activities')) {
            Schema::create('gmao_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('reference')->nullable();
                $table->string('action')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'created_at']);
                $table->index(['subject_type', 'subject_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gmao_activities');
        Schema::dropIfExists('gmao_intervention_reports');
        Schema::dropIfExists('gmao_preventive_plans');
        Schema::dropIfExists('gmao_work_order_parts');
        Schema::dropIfExists('gmao_spare_parts');
        Schema::dropIfExists('gmao_work_orders');
        Schema::dropIfExists('gmao_work_requests');
        Schema::dropIfExists('gmao_technicians');
        Schema::dropIfExists('gmao_equipment');
        Schema::dropIfExists('gmao_locations');
    }
};
