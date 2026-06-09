<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('archive_locations')) {
            Schema::create('archive_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('archive_locations')->nullOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('type', 40);
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'type', 'status'], 'archive_locations_site_type_status_idx');
            });
        }

        if (! Schema::hasTable('archive_containers')) {
            Schema::create('archive_containers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('title');
                $table->string('category', 80)->nullable();
                $table->string('owner_service', 100)->nullable();
                $table->string('period_label', 80)->nullable();
                $table->string('confidentiality_level', 40)->default('internal');
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'status'], 'archive_containers_site_status_idx');
            });
        }

        if (! Schema::hasTable('archive_records')) {
            Schema::create('archive_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
                $table->foreignId('archive_container_id')->nullable()->constrained('archive_containers')->nullOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('title');
                $table->string('document_type', 80)->nullable();
                $table->string('category', 80)->nullable();
                $table->string('owner_service', 100)->nullable();
                $table->date('document_date')->nullable();
                $table->date('archived_at')->nullable();
                $table->date('retention_until')->nullable();
                $table->string('confidentiality_level', 40)->default('internal');
                $table->string('status', 40)->default('archived');
                $table->string('file_path')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'status'], 'archive_records_site_status_idx');
                $table->index(['retention_until', 'status'], 'archive_records_retention_status_idx');
            });
        }

        if (! Schema::hasTable('archive_movements')) {
            Schema::create('archive_movements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignId('archive_record_id')->nullable()->constrained('archive_records')->nullOnDelete();
                $table->foreignId('archive_container_id')->nullable()->constrained('archive_containers')->nullOnDelete();
                $table->foreignId('from_location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
                $table->foreignId('to_location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->timestamp('moved_at')->nullable();
                $table->string('reason', 160)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'moved_at'], 'archive_movements_site_moved_idx');
            });
        }

        if (! Schema::hasTable('archive_retention_rules')) {
            Schema::create('archive_retention_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->string('category', 80);
                $table->unsignedInteger('retention_years')->default(5);
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['company_site_id', 'category'], 'archive_retention_site_category_unique');
            });
        }

        if (! Schema::hasTable('archive_activities')) {
            Schema::create('archive_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject_type', 80)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('action', 60);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'created_at'], 'archive_activities_site_created_idx');
                $table->index(['subject_type', 'subject_id'], 'archive_activities_subject_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_activities');
        Schema::dropIfExists('archive_retention_rules');
        Schema::dropIfExists('archive_movements');
        Schema::dropIfExists('archive_records');
        Schema::dropIfExists('archive_containers');
        Schema::dropIfExists('archive_locations');
    }
};
