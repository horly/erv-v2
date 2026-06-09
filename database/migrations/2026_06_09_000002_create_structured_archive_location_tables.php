<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('archive_rooms')) {
            Schema::create('archive_rooms', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'status']);
            });
        }

        if (! Schema::hasTable('archive_racks')) {
            Schema::create('archive_racks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_room_id')->constrained('archive_rooms')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'archive_room_id', 'status'], 'archive_racks_site_room_status_idx');
            });
        }

        if (! Schema::hasTable('archive_cabinets')) {
            Schema::create('archive_cabinets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_rack_id')->constrained('archive_racks')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'archive_rack_id', 'status'], 'archive_cabinets_site_rack_status_idx');
            });
        }

        if (! Schema::hasTable('archive_shelves')) {
            Schema::create('archive_shelves', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_cabinet_id')->constrained('archive_cabinets')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'archive_cabinet_id', 'status'], 'archive_shelves_site_cabinet_status_idx');
            });
        }

        if (! Schema::hasTable('archive_compartments')) {
            Schema::create('archive_compartments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_shelf_id')->constrained('archive_shelves')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'archive_shelf_id', 'status'], 'archive_compartments_site_shelf_status_idx');
            });
        }

        if (! Schema::hasTable('archive_boxes')) {
            Schema::create('archive_boxes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_shelf_id')->nullable()->constrained('archive_shelves')->nullOnDelete();
                $table->foreignId('archive_compartment_id')->nullable()->constrained('archive_compartments')->nullOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'archive_shelf_id', 'archive_compartment_id'], 'archive_boxes_site_parent_idx');
            });
        }

        if (Schema::hasTable('archive_containers') && ! Schema::hasColumn('archive_containers', 'archive_box_id')) {
            Schema::table('archive_containers', function (Blueprint $table): void {
                $table->foreignId('archive_box_id')->nullable()->after('archive_location_id')->constrained('archive_boxes')->nullOnDelete();
            });
        }

        if (Schema::hasTable('archive_records') && ! Schema::hasColumn('archive_records', 'archive_box_id')) {
            Schema::table('archive_records', function (Blueprint $table): void {
                $table->foreignId('archive_box_id')->nullable()->after('archive_container_id')->constrained('archive_boxes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('archive_records') && Schema::hasColumn('archive_records', 'archive_box_id')) {
            Schema::table('archive_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('archive_box_id'));
        }

        if (Schema::hasTable('archive_containers') && Schema::hasColumn('archive_containers', 'archive_box_id')) {
            Schema::table('archive_containers', fn (Blueprint $table) => $table->dropConstrainedForeignId('archive_box_id'));
        }

        Schema::dropIfExists('archive_boxes');
        Schema::dropIfExists('archive_compartments');
        Schema::dropIfExists('archive_shelves');
        Schema::dropIfExists('archive_cabinets');
        Schema::dropIfExists('archive_racks');
        Schema::dropIfExists('archive_rooms');
    }
};
