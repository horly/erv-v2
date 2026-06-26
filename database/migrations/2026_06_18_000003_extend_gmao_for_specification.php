<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gmao_locations')) {
            Schema::table('gmao_locations', function (Blueprint $table): void {
                if (! Schema::hasColumn('gmao_locations', 'parent_id')) {
                    $table->foreignId('parent_id')
                        ->nullable()
                        ->after('company_site_id')
                        ->constrained('gmao_locations')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('gmao_equipment')) {
            Schema::table('gmao_equipment', function (Blueprint $table): void {
                if (! Schema::hasColumn('gmao_equipment', 'asset_code')) {
                    $table->string('asset_code', 80)->nullable()->after('reference')->index();
                }

                if (! Schema::hasColumn('gmao_equipment', 'supplier')) {
                    $table->string('supplier', 160)->nullable()->after('serial_number');
                }

                if (! Schema::hasColumn('gmao_equipment', 'acquisition_cost')) {
                    $table->decimal('acquisition_cost', 14, 2)->default(0)->after('supplier');
                }

                if (! Schema::hasColumn('gmao_equipment', 'expense_type')) {
                    $table->string('expense_type', 20)->default('capex')->after('acquisition_cost')->index();
                }

                if (! Schema::hasColumn('gmao_equipment', 'cost_center')) {
                    $table->string('cost_center', 80)->nullable()->after('expense_type');
                }

                if (! Schema::hasColumn('gmao_equipment', 'meter_unit')) {
                    $table->string('meter_unit', 40)->nullable()->after('cost_center');
                }

                if (! Schema::hasColumn('gmao_equipment', 'current_meter')) {
                    $table->decimal('current_meter', 14, 2)->default(0)->after('meter_unit');
                }

                if (! Schema::hasColumn('gmao_equipment', 'last_meter_read_at')) {
                    $table->timestamp('last_meter_read_at')->nullable()->after('current_meter');
                }

                if (! Schema::hasColumn('gmao_equipment', 'expected_lifetime_months')) {
                    $table->unsignedInteger('expected_lifetime_months')->nullable()->after('last_meter_read_at');
                }
            });
        }

        if (! Schema::hasTable('gmao_maintenance_routes')) {
            Schema::create('gmao_maintenance_routes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gmao_equipment_category_id')->nullable()->constrained('gmao_equipment_categories')->nullOnDelete();
                $table->string('reference')->index();
                $table->string('title');
                $table->string('frequency')->default('monthly')->index();
                $table->decimal('estimated_duration_hours', 8, 2)->default(0);
                $table->string('status')->default('active')->index();
                $table->text('instructions')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
            });
        }

        if (! Schema::hasTable('gmao_maintenance_tasks')) {
            Schema::create('gmao_maintenance_tasks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('gmao_maintenance_route_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position')->default(1);
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->text('safety_notes')->nullable();
                $table->unsignedInteger('estimated_minutes')->default(0);
                $table->timestamps();

                $table->index(['gmao_maintenance_route_id', 'position']);
            });
        }

        if (Schema::hasTable('gmao_preventive_plans')) {
            Schema::table('gmao_preventive_plans', function (Blueprint $table): void {
                if (! Schema::hasColumn('gmao_preventive_plans', 'gmao_maintenance_route_id')) {
                    $table->foreignId('gmao_maintenance_route_id')
                        ->nullable()
                        ->after('gmao_equipment_id')
                        ->constrained()
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('gmao_preventive_plans', 'trigger_type')) {
                    $table->string('trigger_type', 30)->default('frequency')->after('frequency')->index();
                }

                if (! Schema::hasColumn('gmao_preventive_plans', 'meter_interval')) {
                    $table->decimal('meter_interval', 14, 2)->nullable()->after('trigger_type');
                }

                if (! Schema::hasColumn('gmao_preventive_plans', 'alert_days')) {
                    $table->unsignedInteger('alert_days')->default(7)->after('meter_interval');
                }
            });
        }

        if (Schema::hasTable('gmao_work_orders')) {
            Schema::table('gmao_work_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('gmao_work_orders', 'workflow_stage')) {
                    $table->string('workflow_stage', 40)->default('diagnosis')->after('status')->index();
                }

                if (! Schema::hasColumn('gmao_work_orders', 'failure_started_at')) {
                    $table->timestamp('failure_started_at')->nullable()->after('actual_hours');
                }

                if (! Schema::hasColumn('gmao_work_orders', 'downtime_minutes')) {
                    $table->unsignedInteger('downtime_minutes')->default(0)->after('completed_at');
                }

                if (! Schema::hasColumn('gmao_work_orders', 'labor_cost')) {
                    $table->decimal('labor_cost', 14, 2)->default(0)->after('downtime_minutes');
                }

                if (! Schema::hasColumn('gmao_work_orders', 'external_cost')) {
                    $table->decimal('external_cost', 14, 2)->default(0)->after('labor_cost');
                }

                if (! Schema::hasColumn('gmao_work_orders', 'capex_amount')) {
                    $table->decimal('capex_amount', 14, 2)->default(0)->after('external_cost');
                }

                if (! Schema::hasColumn('gmao_work_orders', 'opex_amount')) {
                    $table->decimal('opex_amount', 14, 2)->default(0)->after('capex_amount');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gmao_maintenance_tasks');
        Schema::dropIfExists('gmao_maintenance_routes');
    }
};
