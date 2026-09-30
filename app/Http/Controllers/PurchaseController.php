<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    private function clearMetricsCache(): void
    {
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('dashboard.data.v1');
        Cache::forget('shared.low_stock_count.v1');
    }

    public function index(Request $request): View
    {
        return view('purchases', [
            'title' => 'Purchases',
            'purchases' => Purchase::with(['supplier:id,name', 'items.material:id,name,unit'])
                ->latest('purchase_date')
                ->paginate(15),
            'materials' => RawMaterial::select(['id', 'name', 'unit', 'current_stock', 'unit_cost'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'suppliers' => Supplier::select(['id', 'name'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'purchaseOrder' => $request->filled('po_id')
                ? PurchaseOrder::with('items.material:id,name,unit')->find($request->integer('po_id'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_id' => ['nullable', 'exists:purchase_orders,id'],
            'purchase_type' => ['required', 'in:po_based,direct'],
            'purchase_date' => ['required', 'date'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['required', 'exists:raw_materials,id'],
            'quantity_received' => ['required', 'array'],
            'quantity_received.*' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'array'],
            'unit_cost.*' => ['required', 'numeric', 'gte:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            $purchaseOrder = ! empty($validated['po_id'])
                ? PurchaseOrder::with('items')->lockForUpdate()->findOrFail($validated['po_id'])
                : null;

            if ($purchaseOrder && ((int) $purchaseOrder->supplier_id !== (int) $validated['supplier_id'] || $purchaseOrder->status !== 'approved')) {
                abort(422, 'The purchase order must be approved and use the selected supplier.');
            }

            $purchase = Purchase::create([
                'po_id' => $purchaseOrder?->id,
                'purchase_type' => $purchaseOrder ? 'po_based' : $validated['purchase_type'],
                'supplier_id' => $validated['supplier_id'],
                'received_by' => auth()->id(),
                'purchase_date' => $validated['purchase_date'],
            ]);

            $upsertData = [];
            $totalPurchaseCost = 0;
            $now = now();

            foreach ($validated['material_id'] as $index => $materialId) {
                $quantity = (float) $validated['quantity_received'][$index];
                $unitCost = (float) $validated['unit_cost'][$index];
                $subtotal = $quantity * $unitCost;
                $totalPurchaseCost += $subtotal;

                $material = RawMaterial::lockForUpdate()->findOrFail($materialId);
                
                // 1. Calculate Weighted Average Cost (WAC)
                $currentStock = (float) $material->current_stock;
                $currentCost  = (float) ($material->unit_cost ?? 0);
                $newStock     = $currentStock + $quantity;

                $newWac = $newStock > 0 
                    ? (($currentStock * $currentCost) + ($quantity * $unitCost)) / $newStock 
                    : $unitCost;

                // 2. Update stock and unit cost
                $material->update([
                    'current_stock' => $newStock,
                    'unit_cost'     => $newWac,
                ]);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'material_id' => $material->id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'purchase_in',
                    'quantity' => $quantity,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'remarks' => 'Direct purchase received',
                ]);

                $upsertData[] = [
                    'supplier_id' => $purchase->supplier_id,
                    'material_id' => $material->id,
                    'last_unit_cost' => $unitCost,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($upsertData)) {
                DB::table('supplier_material')->upsert(
                    $upsertData,
                    ['supplier_id', 'material_id'],
                    ['last_unit_cost', 'updated_at']
                );
            }

            // 3. Automatically record Expense for Sales Monitoring
            $supplierName = Supplier::find($validated['supplier_id'])?->name ?? 'Supplier';
            Expense::create([
                'purchase_id'          => $purchase->id,
                'external_expense_id'  => 'EXP-' . strtoupper(Str::random(12)),
                'description'          => 'Purchase Order: ' . $supplierName,
                'category'             => 'Operating Expense',
                'amount'               => $totalPurchaseCost,
                'expense_date'         => $validated['purchase_date'],
                'source_system'        => 'ChickyVentory',
                'transferred_to_sales' => true,
                'sync_status'          => 'Synced',
            ]);

            if ($purchaseOrder) {
                $ordered = $purchaseOrder->items->sum('quantity_ordered');
                
                $received = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->where('purchases.po_id', $purchaseOrder->id)
                    ->sum('purchase_items.quantity_received');

                $purchaseOrder->update(['status' => $received >= $ordered ? 'fulfilled' : 'partially_fulfilled']);
            }
        });

        $this->clearMetricsCache();

        return to_route('purchases')->with('success', 'Purchase received, ingredient unit cost updated, and expense logged.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->load('items');

            foreach ($purchase->items as $item) {
                $material = RawMaterial::lockForUpdate()->find($item->material_id);
                if ($material) {
                    $material->update(['current_stock' => max(0, $material->current_stock - $item->quantity_received)]);
                }
            }

            // Delete associated expense record automatically
            Expense::where('purchase_id', $purchase->id)->delete();

            StockMovement::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            if ($purchase->po_id) {
                $purchaseOrder = PurchaseOrder::find($purchase->po_id);
                if ($purchaseOrder && in_array($purchaseOrder->status, ['fulfilled', 'partially_fulfilled'], true)) {
                    $purchaseOrder->update(['status' => 'approved']);
                }
            }

            $purchase->items()->delete();
            $purchase->delete();
        });

        $this->clearMetricsCache();

        return to_route('purchases')->with('success', 'Purchase deleted, stock reversed, and linked expense removed.');
    }
}