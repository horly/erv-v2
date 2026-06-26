<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gmao_equipment_categories')) {
            Schema::create('gmao_equipment_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
                $table->string('reference')->index();
                $table->string('code', 40)->nullable();
                $table->string('name', 120);
                $table->string('family', 80)->nullable();
                $table->string('default_criticality', 30)->default('medium');
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->timestamps();

                $table->unique(['company_site_id', 'reference']);
                $table->unique(['company_site_id', 'name']);
            });
        }

        if (Schema::hasTable('gmao_equipment') && ! Schema::hasColumn('gmao_equipment', 'gmao_equipment_category_id')) {
            Schema::table('gmao_equipment', function (Blueprint $table): void {
                $table->foreignId('gmao_equipment_category_id')
                    ->nullable()
                    ->after('gmao_location_id')
                    ->constrained('gmao_equipment_categories')
                    ->nullOnDelete();
            });
        }

        $this->migrateExistingEquipmentCategories();
    }

    public function down(): void
    {
        if (Schema::hasTable('gmao_equipment') && Schema::hasColumn('gmao_equipment', 'gmao_equipment_category_id')) {
            Schema::table('gmao_equipment', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('gmao_equipment_category_id');
            });
        }

        Schema::dropIfExists('gmao_equipment_categories');
    }

    private function migrateExistingEquipmentCategories(): void
    {
        if (! Schema::hasTable('gmao_equipment') || ! Schema::hasColumn('gmao_equipment', 'gmao_equipment_category_id')) {
            return;
        }

        DB::table('gmao_equipment')
            ->whereNull('gmao_equipment_category_id')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->orderBy('company_site_id')
            ->orderBy('category')
            ->select(['id', 'company_site_id', 'category'])
            ->get()
            ->each(function (object $equipment): void {
                $categoryName = trim((string) $equipment->category);

                if ($categoryName === '') {
                    return;
                }

                $categoryId = DB::table('gmao_equipment_categories')
                    ->where('company_site_id', $equipment->company_site_id)
                    ->where('name', $categoryName)
                    ->value('id');

                if (! $categoryId) {
                    $categoryId = DB::table('gmao_equipment_categories')->insertGetId([
                        'company_site_id' => $equipment->company_site_id,
                        'reference' => $this->nextCategoryReference((int) $equipment->company_site_id),
                        'code' => Str::upper(Str::limit(Str::slug($categoryName, '-'), 40, '')),
                        'name' => $categoryName,
                        'family' => null,
                        'default_criticality' => 'medium',
                        'description' => null,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('gmao_equipment')
                    ->where('id', $equipment->id)
                    ->update(['gmao_equipment_category_id' => $categoryId]);
            });
    }

    private function nextCategoryReference(int $siteId): string
    {
        $next = ((int) DB::table('gmao_equipment_categories')
            ->where('company_site_id', $siteId)
            ->max('id')) + 1;

        do {
            $reference = 'CAT-'.str_pad((string) $next++, 6, '0', STR_PAD_LEFT);
        } while (DB::table('gmao_equipment_categories')->where('company_site_id', $siteId)->where('reference', $reference)->exists());

        return $reference;
    }
};
