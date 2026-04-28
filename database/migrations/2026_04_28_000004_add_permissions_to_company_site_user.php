<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_site_user', function (Blueprint $table) {
            $table->json('module_permissions')->nullable()->after('user_id');
            $table->boolean('can_create')->default(false)->after('module_permissions');
            $table->boolean('can_update')->default(false)->after('can_create');
            $table->boolean('can_delete')->default(false)->after('can_update');
        });
    }

    public function down(): void
    {
        Schema::table('company_site_user', function (Blueprint $table) {
            $table->dropColumn(['module_permissions', 'can_create', 'can_update', 'can_delete']);
        });
    }
};
