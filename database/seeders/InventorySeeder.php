<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = collect([
            ['name' => 'ABC Supplier', 'contact_person' => 'Maria Santos', 'phone' => '0917 555 0123', 'email' => 'abc@example.com'],
            ['name' => 'XYZ Supplier', 'contact_person' => 'John Reyes', 'phone' => '0918 555 0456', 'email' => 'xyz@example.com'],
            ['name' => 'Fresh Farms', 'contact_person' => 'Ana Cruz', 'phone' => '0919 555 0789', 'email' => 'fresh@example.com'],
            ['name' => 'Pack Right', 'contact_person' => 'Leo Garcia', 'phone' => '0920 555 0100', 'email' => 'pack@example.com'],
        ])->mapWithKeys(fn (array $supplier) => [$supplier['name'] => Supplier::firstOrCreate(['name' => $supplier['name']], $supplier)]);

        $suppliers['ABC Supplier']->update(['requires_po' => true]);
        $suppliers['Fresh Farms']->update(['requires_po' => true]);

        foreach ([
            ['material_code' => 'RM001', 'name' => 'Chicken Breast', 'unit' => 'kg', 'current_stock' => 50, 'minimum_stock' => 15],
            ['material_code' => 'RM002', 'name' => 'Cooking Oil', 'unit' => 'L', 'current_stock' => 25, 'minimum_stock' => 10],
            ['material_code' => 'RM003', 'name' => 'Rice Flour', 'unit' => 'kg', 'current_stock' => 40, 'minimum_stock' => 15],
        ] as $material) {
            \App\Models\RawMaterial::updateOrCreate(['material_code' => $material['material_code']], $material + ['status' => 'active']);
        }

        $products = [
            ['product_code' => 'P001', 'name' => 'Rice', 'category' => 'Ingredients', 'supplier_id' => $suppliers['ABC Supplier']->id, 'current_stock' => 50, 'minimum_stock' => 20, 'unit' => 'kg'],
            ['product_code' => 'P002', 'name' => 'Cooking Oil', 'category' => 'Ingredients', 'supplier_id' => $suppliers['XYZ Supplier']->id, 'current_stock' => 5, 'minimum_stock' => 10, 'unit' => 'L'],
            ['product_code' => 'P003', 'name' => 'Sugar', 'category' => 'Ingredients', 'supplier_id' => $suppliers['ABC Supplier']->id, 'current_stock' => 30, 'minimum_stock' => 15, 'unit' => 'kg'],
            ['product_code' => 'P004', 'name' => 'Chicken', 'category' => 'Ingredients', 'supplier_id' => $suppliers['Fresh Farms']->id, 'current_stock' => 8, 'minimum_stock' => 15, 'unit' => 'kg'],
            ['product_code' => 'P005', 'name' => 'Paper Boxes', 'category' => 'Packaging', 'supplier_id' => $suppliers['Pack Right']->id, 'current_stock' => 25, 'minimum_stock' => 50, 'unit' => 'pcs'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['product_code' => $product['product_code']], $product);
        }

        if (InventoryTransaction::doesntExist()) {
            $rice = Product::where('product_code', 'P001')->firstOrFail();
            $chicken = Product::where('product_code', 'P004')->firstOrFail();
            $oil = Product::where('product_code', 'P002')->firstOrFail();

            InventoryTransaction::insert([
                ['transaction_code' => 'TXN-SEED-001', 'product_id' => $chicken->id, 'reference' => 'ORD-1005', 'type' => 'stock_out', 'quantity' => 12, 'source' => 'Ordering System', 'status' => 'completed', 'occurred_at' => now()->subHours(2), 'created_at' => now(), 'updated_at' => now()],
                ['transaction_code' => 'TXN-SEED-002', 'product_id' => $rice->id, 'reference' => 'PO-001', 'type' => 'stock_in', 'quantity' => 50, 'source' => 'Manual Entry', 'status' => 'completed', 'occurred_at' => now()->subHours(4), 'created_at' => now(), 'updated_at' => now()],
                ['transaction_code' => 'TXN-SEED-003', 'product_id' => $oil->id, 'reference' => 'ORD-1004', 'type' => 'stock_out', 'quantity' => 5, 'source' => 'Ordering System', 'status' => 'completed', 'occurred_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        foreach ([
            'system_name' => 'Chicky Fryday Inventory Management System',
            'currency' => 'PHP',
            'description' => 'Inventory management for Chicky Fryday.',
            'low_stock_threshold' => '10',
            'default_unit' => 'Pieces',
        ] as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
