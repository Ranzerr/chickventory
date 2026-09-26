<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper to safely add indexes if they don't already exist
        $addIndexSafely = function (string $table, array $columns, ?string $indexName = null) {
            if (! Schema::hasTable($table)) {
                return;
            }

            $name = $indexName ?? $table . '_' . implode('_', $columns) . '_index';

            if (! Schema::hasIndex($table, $name)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($columns, $name) {
                    $tableSchema->index($columns, $name);
                });
            }
        };

        // 1. Inventory Transactions Indexes
        $addIndexSafely('inventory_transactions', ['type', 'occurred_at'], 'idx_inv_tx_type_occurred');
        $addIndexSafely('inventory_transactions', ['product_id', 'occurred_at'], 'idx_inv_tx_prod_occurred');
        $addIndexSafely('inventory_transactions', ['occurred_at'], 'idx_inv_tx_occurred');

        // 2. Stock Movements Indexes
        $addIndexSafely('stock_movements', ['movement_type', 'created_at'], 'idx_sm_type_created');
        $addIndexSafely('stock_movements', ['material_id', 'created_at'], 'idx_sm_mat_created');
        $addIndexSafely('stock_movements', ['performed_by'], 'idx_sm_performed');

        // 3. Products Indexes
        $addIndexSafely('products', ['status', 'name'], 'idx_prod_status_name');
        $addIndexSafely('products', ['supplier_id'], 'idx_prod_supplier');
        $addIndexSafely('products', ['status', 'current_stock', 'minimum_stock'], 'idx_prod_status_stocks');

        // 4. Raw Materials Indexes
        $addIndexSafely('raw_materials', ['status', 'name'], 'idx_rm_status_name');
        $addIndexSafely('raw_materials', ['status', 'current_stock', 'minimum_stock'], 'idx_rm_status_stocks');

        // 5. Purchases & Purchase Items Indexes
        $addIndexSafely('purchases', ['purchase_type', 'purchase_date'], 'idx_pur_type_date');
        $addIndexSafely('purchases', ['po_id'], 'idx_pur_po');
        $addIndexSafely('purchases', ['supplier_id'], 'idx_pur_supplier');
        $addIndexSafely('purchases', ['received_by'], 'idx_pur_received');

        $addIndexSafely('purchase_items', ['purchase_id'], 'idx_pi_purchase');
        $addIndexSafely('purchase_items', ['material_id'], 'idx_pi_material');

        // 6. Purchase Orders & Purchase Order Items Indexes
        $addIndexSafely('purchase_orders', ['status', 'order_date'], 'idx_po_status_date');
        $addIndexSafely('purchase_orders', ['supplier_id'], 'idx_po_supplier');
        $addIndexSafely('purchase_orders', ['created_by'], 'idx_po_created');

        $addIndexSafely('purchase_order_items', ['purchase_order_id'], 'idx_poi_po');
        $addIndexSafely('purchase_order_items', ['material_id'], 'idx_poi_material');

        // 7. Expenses Indexes
        $addIndexSafely('expenses', ['expense_date'], 'idx_exp_date');
        $addIndexSafely('expenses', ['purchase_id'], 'idx_exp_purchase');
        $addIndexSafely('expenses', ['transferred_to_sales', 'expense_date'], 'idx_exp_trans_date');

        // 8. Order Items Indexes
        $addIndexSafely('order_items', ['order_date'], 'idx_oi_date');
        $addIndexSafely('order_items', ['product_id', 'order_date'], 'idx_oi_prod_date');
        $addIndexSafely('order_items', ['external_order_id'], 'idx_oi_ext_order');

        // 9. Suppliers Indexes
        $addIndexSafely('suppliers', ['status', 'name'], 'idx_sup_status_name');

        // 10. System Settings Indexes
        if (Schema::hasTable('system_settings') && ! Schema::hasIndex('system_settings', 'sys_settings_key_unique')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->unique('key', 'sys_settings_key_unique');
            });
        }

        // 11. Users Indexes
        $addIndexSafely('users', ['name'], 'idx_usr_name');
        $addIndexSafely('users', ['status'], 'idx_usr_status');
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

        $dropIndexSafely('inventory_transactions', 'idx_inv_tx_type_occurred');
        $dropIndexSafely('inventory_transactions', 'idx_inv_tx_prod_occurred');
        $dropIndexSafely('inventory_transactions', 'idx_inv_tx_occurred');

        $dropIndexSafely('stock_movements', 'idx_sm_type_created');
        $dropIndexSafely('stock_movements', 'idx_sm_mat_created');
        $dropIndexSafely('stock_movements', 'idx_sm_performed');

        $dropIndexSafely('products', 'idx_prod_status_name');
        $dropIndexSafely('products', 'idx_prod_supplier');
        $dropIndexSafely('products', 'idx_prod_status_stocks');

        $dropIndexSafely('raw_materials', 'idx_rm_status_name');
        $dropIndexSafely('raw_materials', 'idx_rm_status_stocks');

        $dropIndexSafely('purchases', 'idx_pur_type_date');
        $dropIndexSafely('purchases', 'idx_pur_po');
        $dropIndexSafely('purchases', 'idx_pur_supplier');
        $dropIndexSafely('purchases', 'idx_pur_received');

        $dropIndexSafely('purchase_items', 'idx_pi_purchase');
        $dropIndexSafely('purchase_items', 'idx_pi_material');

        $dropIndexSafely('purchase_orders', 'idx_po_status_date');
        $dropIndexSafely('purchase_orders', 'idx_po_supplier');
        $dropIndexSafely('purchase_orders', 'idx_po_created');

        $dropIndexSafely('purchase_order_items', 'idx_poi_po');
        $dropIndexSafely('purchase_order_items', 'idx_poi_material');

        $dropIndexSafely('expenses', 'idx_exp_date');
        $dropIndexSafely('expenses', 'idx_exp_purchase');
        $dropIndexSafely('expenses', 'idx_exp_trans_date');

        $dropIndexSafely('order_items', 'idx_oi_date');
        $dropIndexSafely('order_items', 'idx_oi_prod_date');
        $dropIndexSafely('order_items', 'idx_oi_ext_order');

        $dropIndexSafely('suppliers', 'idx_sup_status_name');

        if (Schema::hasTable('system_settings') && Schema::hasIndex('system_settings', 'sys_settings_key_unique')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropUnique('sys_settings_key_unique');
            });
        }

        $dropIndexSafely('users', 'idx_usr_name');
        $dropIndexSafely('users', 'idx_usr_status');
    }
};