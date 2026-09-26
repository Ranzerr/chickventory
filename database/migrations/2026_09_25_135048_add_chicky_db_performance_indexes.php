<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add optimized indexes.
     */
    public function up(): void
    {
        $addIndexSafely = function (string $table, array $columns, string $indexName) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($columns, $indexName) {
                    $tableSchema->index($columns, $indexName);
                });
            }
        };

        // 1. Products Indexes (Primary Key: product_id)
        $addIndexSafely('products', ['status', 'name'], 'idx_prod_status_name');
        $addIndexSafely('products', ['status', 'current_stock', 'minimum_stock'], 'idx_prod_stocks');

        // 2. Stock Movements Indexes
        $addIndexSafely('stock_movements', ['movement_type', 'created_at'], 'idx_sm_type_created');
        $addIndexSafely('stock_movements', ['material_id', 'created_at'], 'idx_sm_mat_created');

        // 3. Expenses Indexes
        $addIndexSafely('expenses', ['expense_date'], 'idx_exp_date');
        $addIndexSafely('expenses', ['transferred_to_sales', 'expense_date'], 'idx_exp_trans_date');

        // 4. POS Legacy & Sales Tables
        $addIndexSafely('tbl_orders', ['created_at', 'order_status'], 'idx_tbl_orders_created_status');
        $addIndexSafely('sales', ['sale_date', 'status'], 'idx_sales_date_status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dropIndexSafely = function (string $table, string $indexName) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($indexName) {
                    $tableSchema->dropIndex($indexName);
                });
            }
        };

        $dropIndexSafely('products', 'idx_prod_status_name');
        $dropIndexSafely('products', 'idx_prod_stocks');
        $dropIndexSafely('stock_movements', 'idx_sm_type_created');
        $dropIndexSafely('stock_movements', 'idx_sm_mat_created');
        $dropIndexSafely('expenses', 'idx_exp_date');
        $dropIndexSafely('expenses', 'idx_exp_trans_date');
        $dropIndexSafely('tbl_orders', 'idx_tbl_orders_created_status');
        $dropIndexSafely('sales', 'idx_sales_date_status');
    }
};