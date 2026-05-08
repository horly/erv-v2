<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_delivery_note_serials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_note_line_id')->constrained('accounting_delivery_note_lines')->cascadeOnDelete();
            $table->string('serial_number');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['delivery_note_line_id', 'position'], 'acct_delivery_serial_line_position_idx');
            $table->unique(['delivery_note_line_id', 'serial_number'], 'acct_delivery_serial_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_delivery_note_serials');
    }
};
