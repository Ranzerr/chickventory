<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $selectedItem = null;
        if ($request->filled('material_id')) {
            $selectedItem = 'ingredient:'.$request->input('material_id');
        } elseif ($request->filled('product_id')) {
            $selectedItem = 'product:'.$request->input('product_id');
        }

        $recentProductStockIns = InventoryTransaction::with('product')
            ->where('type', 'stock_in')
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn ($txn) => (object) [
                'reference' => $txn->reference ?: $txn->transaction_code,
                'date' => $txn->occurred_at,
                'item_name' => $txn->product?->name ?? 'Unknown',
                'item_type' => 'Product',
                'quantity' => $txn->quantity,
                'unit' => $txn->product?->unit ?? 'pcs',
                'status' => $txn->status,
            ]);

        $recentMaterialStockIns = StockMovement::with('material')
            ->whereIn('movement_type', ['stock_in', 'purchase_in'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($mov) => (object) [
                'reference' => $mov->remarks ?: 'Manual Stock In',
                'date' => $mov->created_at,
                'item_name' => $mov->material?->name ?? 'Unknown',
                'item_type' => 'Ingredient',
                'quantity' => $mov->quantity,
                'unit' => $mov->material?->unit ?? 'pcs',
                'status' => 'completed',
            ]);

        $recentStockIns = $recentProductStockIns->concat($recentMaterialStockIns)
            ->sortByDesc(fn ($item) => $item->date?->timestamp ?? 0)
            ->values()
            ->take(10);

        return view('stock-in', [
            'title' => 'Stock In',
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'rawMaterials' => RawMaterial::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'recentStockIns' => $recentStockIns,
            'selectedItem' => $selectedItem,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stock_item' => ['nullable', 'string'],
            'product_id' => ['nullable', 'exists:products,id'],
            'material_id' => ['nullable', 'exists:raw_materials,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'date_received' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = null;
        $itemId = null;

        if (! empty($validated['stock_item']) && str_contains($validated['stock_item'], ':')) {
            [$type, $itemId] = explode(':', $validated['stock_item'], 2);
        } elseif (! empty($validated['material_id'])) {
            $type = 'ingredient';
            $itemId = $validated['material_id'];
        } elseif (! empty($validated['product_id'])) {
            $type = 'product';
            $itemId = $validated['product_id'];
        } else {
            return back()->withErrors(['stock_item' => 'Please select an ingredient or product to stock in.'])->withInput();
        }

        if ($type === 'ingredient') {
            DB::transaction(function () use ($itemId, $validated): void {
                $material = RawMaterial::lockForUpdate()->findOrFail($itemId);
                $material->increment('current_stock', $validated['quantity']);

                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'stock_in',
                    'quantity' => $validated['quantity'],
                    'reference_type' => 'manual',
                    'performed_by' => auth()->id(),
                    'remarks' => $validated['remarks'] ?: 'Manual stock in',
                ]);
            });

            cache()->forget('dashboard.metrics.v3');
            cache()->forget('shared.low_stock_count.v1');

            return to_route('stock-in')->with('success', 'Ingredient stock received successfully.');
        }

        DB::transaction(function () use ($itemId, $validated): void {
            $product = Product::lockForUpdate()->findOrFail($itemId);
            $product->increment('current_stock', $validated['quantity']);

            InventoryTransaction::create([
                'transaction_code' => 'TXN-'.str()->upper(str()->random(8)),
                'product_id' => $product->id,
                'reference' => 'PO-'.str()->upper(str()->random(5)),
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'source' => 'Manual Entry',
                'status' => 'completed',
                'occurred_at' => $validated['date_received'],
            ]);
        });

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return to_route('stock-in')->with('success', 'Product stock received successfully.');
    }
}
