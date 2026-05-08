<?php

use App\Models\AccountingClient;
use App\Models\AccountingCustomerOrder;
use App\Models\AccountingCustomerOrderLine;
use App\Models\AccountingService;
use App\Models\AccountingStockItem;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_delivery_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->foreignIdFor(AccountingCustomerOrder::class, 'customer_order_id')->nullable()->constrained('accounting_customer_orders')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('delivery_date');
            $table->string('status', 30)->default('draft');
            $table->string('delivered_by')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('stock_released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_delivery_note_site_status_idx');
            $table->index(['company_site_id', 'delivery_date'], 'acct_delivery_note_site_date_idx');
            $table->index(['company_site_id', 'client_id'], 'acct_delivery_note_site_client_idx');
            $table->index(['customer_order_id', 'status'], 'acct_delivery_note_order_status_idx');
        });

        Schema::create('accounting_delivery_note_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('accounting_delivery_notes')->cascadeOnDelete();
            $table->foreignIdFor(AccountingCustomerOrderLine::class, 'customer_order_line_id')->nullable()->constrained('accounting_customer_order_lines')->nullOnDelete();
            $table->string('line_type', 30);
            $table->foreignIdFor(AccountingStockItem::class, 'item_id')->nullable()->constrained('accounting_stock_items')->nullOnDelete();
            $table->foreignIdFor(AccountingService::class, 'service_id')->nullable()->constrained('accounting_services')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('ordered_quantity', 18, 2)->default(0);
            $table->decimal('already_delivered_quantity', 18, 2)->default(0);
            $table->decimal('quantity', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['delivery_note_id', 'line_type'], 'acct_delivery_note_line_type_idx');
            $table->index(['customer_order_line_id'], 'acct_delivery_note_line_order_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_delivery_note_lines');
        Schema::dropIfExists('accounting_delivery_notes');
    }
};
