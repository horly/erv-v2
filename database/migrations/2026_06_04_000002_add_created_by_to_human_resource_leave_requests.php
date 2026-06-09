<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('human_resource_leave_requests', function (Blueprint $table): void {
            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->after('human_resource_employee_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('human_resource_leave_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
