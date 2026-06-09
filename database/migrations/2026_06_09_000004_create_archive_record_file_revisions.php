<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('archive_record_file_revisions')) {
            Schema::create('archive_record_file_revisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('archive_record_id')->constrained('archive_records')->cascadeOnDelete();
                $table->foreignIdFor(User::class, 'changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('old_file_path')->nullable();
                $table->string('new_file_path');
                $table->string('reason', 255);
                $table->timestamps();

                $table->index(['archive_record_id', 'created_at'], 'archive_record_file_revisions_record_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_record_file_revisions');
    }
};
