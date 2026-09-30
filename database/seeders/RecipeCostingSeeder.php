<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Database\Seeder;

class RecipeCostingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or update Raw Materials with sample costs
        $chicken = RawMaterial::updateOrCreate(
            ['material_code' => 'RM-CHK-01'],
            [
                'name'          => 'Raw Chicken Cuts',
                'unit'          => 'kg',
                'unit_cost'     => 180.0000, // ₱180 per kg
                'yield_percent' => 90.00,    // 90% usable -> Effective cost ₱200/kg
                'current_stock' => 50.00,
                'minimum_stock' => 10.00,
                'status'        => 'active',
            ]
        );

        $sauce = RawMaterial::updateOrCreate(
            ['material_code' => 'RM-SAU-01'],
            [
                'name'          => 'Classic Buffalo Sauce',
                'unit'          => 'L',
                'unit_cost'     => 120.0000, // ₱120 per liter
                'yield_percent' => 100.00,   // 100% yield -> Effective cost ₱120/L
                'current_stock' => 20.00,
                'minimum_stock' => 5.00,
                'status'        => 'active',
            ]
        );

        // 2. Fetch or create a target Product
        $product = Product::firstOrCreate(
            ['product_code' => 'PRD-001'],
            [
                'name'          => 'Classic Buffalo (Rice Meal)',
                'category'      => 'Chicky Pops',
                'price'         => 170.00,
                'current_stock' => 100.00,
                'minimum_stock' => 10.00,
                'unit'          => 'pcs',
                'status'        => 'available',
            ]
        );

        // 3. Attach ingredients using unit conversion factors
        $product->recipeMaterials()->sync([
            $chicken->id => [
                'quantity_required' => 200.0000, // 200 grams
                'unit'              => 'g',
                'conversion_factor' => 0.001000, // converts g to kg
            ],
            $sauce->id => [
                'quantity_required' => 30.0000,  // 30 ml
                'unit'              => 'ml',
                'conversion_factor' => 0.001000, // converts ml to L
            ],
        ]);

        $product->load('recipes.rawMaterial');

        $this->command->info("--- Recipe Costing Test Result ---");
        $this->command->info("Product: {$product->name}");
        $this->command->info("Selling Price: ₱{$product->price}");
        $this->command->info("Calculated COGS: ₱" . number_format($product->cost_to_produce, 2));
        $this->command->info("Profit Margin: ₱" . number_format($product->profit_margin, 2));
        $this->command->info("Margin %: " . number_format($product->profit_margin_percentage, 2) . "%");
    }
}