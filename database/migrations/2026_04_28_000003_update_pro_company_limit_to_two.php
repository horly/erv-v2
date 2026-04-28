<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscriptions')
            ->where('type', 'pro')
            ->update(['company_limit' => 2]);
    }

    public function down(): void
    {
        DB::table('subscriptions')
            ->where('type', 'pro')
            ->update(['company_limit' => 3]);
    }
};
