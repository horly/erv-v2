<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_management_folders')) {
            Schema::create('document_management_folders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name');
                $table->string('category', 80)->nullable();
                $table->string('status', 40)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_site_id', 'status'], 'ged_folders_site_status_idx');
            });
        }

        if (! Schema::hasTable('document_management_records')) {
            Schema::create('document_management_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
                $table->unsignedBigInteger('document_management_folder_id')->nullable();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignIdFor(User::class, 'assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('record_type', 40);
                $table->string('direction', 30)->default('internal');
                $table->string('subject');
                $table->string('sender')->nullable();
                $table->string('recipient')->nullable();
                $table->string('category', 80)->nullable();
                $table->string('priority', 30)->default('normal');
                $table->string('status', 40)->default('registered');
                $table->date('received_at')->nullable();
                $table->date('sent_at')->nullable();
                $table->date('due_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->string('file_path')->nullable();
                $table->text('summary')->nullable();
                $table->text('decision')->nullable();
                $table->timestamps();

                $table->foreign('document_management_folder_id', 'ged_records_folder_fk')->references('id')->on('document_management_folders')->nullOnDelete();
                $table->index(['company_site_id', 'record_type', 'status'], 'ged_records_site_type_status_idx');
                $table->index(['company_site_id', 'assigned_to', 'status'], 'ged_records_assignment_idx');
                $table->index(['due_at', 'priority'], 'ged_records_due_priority_idx');
            });
        }

        if (! Schema::hasTable('document_management_activities')) {
            Schema::create('document_management_activities', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_management_record_id');
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->foreign('document_management_record_id', 'ged_activities_record_fk')->references('id')->on('document_management_records')->cascadeOnDelete();
                $table->index(['document_management_record_id', 'created_at'], 'ged_activities_record_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_management_activities');
        Schema::dropIfExists('document_management_records');
        Schema::dropIfExists('document_management_folders');
    }
};
