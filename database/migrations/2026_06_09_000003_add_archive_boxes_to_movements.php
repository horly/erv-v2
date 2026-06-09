<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('archive_movements') && ! Schema::hasColumn('archive_movements', 'from_archive_box_id')) {
            Schema::table('archive_movements', function (Blueprint $table): void {
                $table->foreignId('from_archive_box_id')->nullable()->after('to_location_id')->constrained('archive_boxes')->nullOnDelete();
                $table->foreignId('to_archive_box_id')->nullable()->after('from_archive_box_id')->constrained('archive_boxes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('archive_movements') && Schema::hasColumn('archive_movements', 'from_archive_box_id')) {
            Schema::table('archive_movements', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('to_archive_box_id');
                $table->dropConstrainedForeignId('from_archive_box_id');
            });
        }
    }
};
