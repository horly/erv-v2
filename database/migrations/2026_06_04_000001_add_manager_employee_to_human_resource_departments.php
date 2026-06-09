<?php

use App\Models\HumanResourceEmployee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('human_resource_departments', function (Blueprint $table): void {
            if (! Schema::hasColumn('human_resource_departments', 'manager_employee_id')) {
                $table->foreignIdFor(HumanResourceEmployee::class, 'manager_employee_id')
                    ->nullable()
                    ->after('manager_user_id')
                    ->constrained('human_resource_employees')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('human_resource_departments', function (Blueprint $table): void {
            if (Schema::hasColumn('human_resource_departments', 'manager_employee_id')) {
                $table->dropConstrainedForeignId('manager_employee_id');
            }
        });
    }
};
