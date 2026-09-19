<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeStockService
{
    public function deductStockForProduct(Product $product, int $quantityMade, ?OrderItem $order = null): void
    {
        if ($quantityMade < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $product->load('recipeMaterials');
        if ($product->recipeMaterials->isEmpty()) {
            throw ValidationException::withMessages([
                'product_id' => "{$product->name} does not have a recipe yet.",
            ]);
        }
        $requirements = $product->recipeMaterials->mapWithKeys(fn ($material) => [
            $material->id => $material->pivot->quantity_required * $quantityMade,
        ]);

        DB::transaction(function () use ($requirements, $order): void {
            $materials = \App\Models\RawMaterial::whereIn('id', $requirements->keys())->lockForUpdate()->get();
            foreach ($materials as $material) {
                $quantity = $requirements[$material->id];
                if ($material->current_stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for {$material->name}.",
                    ]);
                }
            }

            foreach ($materials as $material) {
                $quantity = $requirements[$material->id];
                $material->decrement('current_stock', $quantity);
                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'usage_out',
                    'quantity' => -$quantity,
                    'reference_type' => $order ? 'order_item' : 'manual',
                    'reference_id' => $order?->id,
                    'remarks' => $order ? "External order {$order->external_order_id}" : 'Recipe deduction',
                ]);
            }
        });
    }

    public function restockForOrder(OrderItem $order): void
    {
        $product = $order->product;
        $product->load('recipeMaterials');

        $requirements = $product->recipeMaterials->mapWithKeys(fn ($material) => [
            $material->id => $material->pivot->quantity_required * $order->quantity,
        ]);

        DB::transaction(function () use ($requirements, $order): void {
            $materials = \App\Models\RawMaterial::whereIn('id', $requirements->keys())->lockForUpdate()->get();
            foreach ($materials as $material) {
                $quantity = $requirements[$material->id];
                $material->increment('current_stock', $quantity);
                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'usage_reversal',
                    'quantity' => $quantity,
                    'reference_type' => 'order_item',
                    'reference_id' => $order->id,
                    'remarks' => "Reversed for deleted order {$order->external_order_id}",
                ]);
            }
        });
    }
}
