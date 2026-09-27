<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add cost tracking & yield to Raw Materials
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->default(0.0000)->after('unit');
            $table->decimal('yield_percent', 5, 2)->default(100.00)->after('unit_cost');
        });

        // 2. Add recipe usage units & conversion multipliers
        Schema::table('product_recipes', function (Blueprint $table) {
            $table->string('unit', 30)->default('pcs')->after('quantity_required');
            $table->decimal('conversion_factor', 10, 6)->default(1.000000)->after('unit');
        });

        // 3. Add COGS snapshot field to order line items
        Schema::table('tbl_order_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0.00)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'yield_percent']);
        });

        Schema::table('product_recipes', function (Blueprint $table) {
            $table->dropColumn(['unit', 'conversion_factor']);
        });

        Schema::table('tbl_order_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};