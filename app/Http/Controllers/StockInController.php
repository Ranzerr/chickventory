<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockInController extends Controller
{
    private function clearMetricsCache(): void
    {
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('dashboard.data.v1');
        Cache::forget('shared.low_stock_count.v1');
    }

    public function index(Request $request): View
    {
        $selectedItem = null;
        if ($request->filled('material_id')) {
            $selectedItem = 'ingredient:'.$request->input('material_id');
        } elseif ($request->filled('product_id')) {
            $selectedItem = 'product:'.$request->input('product_id');
        }

        // DB-level UNION query for top 10 recent stock-in events
        $productQuery = DB::table('inventory_transactions as it')
            ->leftJoin('products as p', 'it.product_id', '=', 'p.product_id')
            ->select([
                DB::raw("COALESCE(it.reference, it.transaction_code) as reference"),
                'it.occurred_at as date',
                DB::raw("COALESCE(p.name, 'Unknown') as item_name"),
                DB::raw("'Product' as item_type"),
                'it.quantity',
                DB::raw("COALESCE(p.unit, 'pcs') as unit"),
                'it.status',
            ])
            ->where('it.type', 'stock_in');

        $materialQuery = DB::table('stock_movements as sm')
            ->leftJoin('raw_materials as rm', 'sm.material_id', '=', 'rm.id')
            ->select([
                DB::raw("COALESCE(sm.remarks, 'Manual Stock In') as reference"),
                'sm.created_at as date',
                DB::raw("COALESCE(rm.name, 'Unknown') as item_name"),
                DB::raw("'Ingredient' as item_type"),
                'sm.quantity',
                DB::raw("COALESCE(rm.unit, 'pcs') as unit"),
                DB::raw("'completed' as status"),
            ])
            ->whereIn('sm.movement_type', ['stock_in', 'purchase_in']);

        $unionQuery = $productQuery->unionAll($materialQuery);

        $recentStockIns = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        return view('stock-in', [
            'title' => 'Stock In',
            'products' => Product::select(['product_id as id', 'product_code', 'name', 'unit'])
                ->whereIn('status', ['active', 'available'])
                ->orderBy('name')
                ->get(),
            'rawMaterials' => RawMaterial::select(['id', 'material_code', 'name', 'unit'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'suppliers' => Supplier::select(['id', 'name'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'recentStockIns' => $recentStockIns,
            'selectedItem' => $selectedItem,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stock_item' => ['nullable', 'string'],
            'product_id' => ['nullable', 'exists:products,product_id'],
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

            $this->clearMetricsCache();

            return to_route('stock-in')->with('success', 'Ingredient stock received successfully.');
        }

        DB::transaction(function () use ($itemId, $validated): void {
            $product = Product::lockForUpdate()->findOrFail($itemId);
            $product->increment('current_stock', $validated['quantity']);

            InventoryTransaction::create([
                'transaction_code' => 'TXN-'.str()->upper(str()->random(8)),
                'product_id' => $product->getKey(),
                'reference' => 'PO-'.str()->upper(str()->random(5)),
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'source' => 'Manual Entry',
                'status' => 'completed',
                'occurred_at' => $validated['date_received'],
            ]);
        });

        $this->clearMetricsCache();

        return to_route('stock-in')->with('success', 'Product stock received successfully.');
    }
}