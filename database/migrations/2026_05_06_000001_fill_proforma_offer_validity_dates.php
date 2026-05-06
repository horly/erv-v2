<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('accounting_proforma_invoices')
            ->whereNull('expiration_date')
            ->select(['id', 'created_at'])
            ->chunkById(100, function ($proformas): void {
                foreach ($proformas as $proforma) {
                    DB::table('accounting_proforma_invoices')
                        ->where('id', $proforma->id)
                        ->update([
                            'expiration_date' => $proforma->created_at
                                ? date('Y-m-d', strtotime($proforma->created_at))
                                : now()->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
