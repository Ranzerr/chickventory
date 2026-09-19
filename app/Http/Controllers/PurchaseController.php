<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        return view('purchases', [
            'title' => 'Purchases',
            'purchases' => Purchase::with(['supplier', 'items.material'])->latest('purchase_date')->limit(50)->get(),
            'materials' => RawMaterial::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'purchaseOrder' => $request->filled('po_id') ? PurchaseOrder::with('items.material')->find($request->integer('po_id')) : null,
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

            foreach ($validated['material_id'] as $index => $materialId) {
                $quantity = $validated['quantity_received'][$index];
                $unitCost = $validated['unit_cost'][$index];
                $material = RawMaterial::lockForUpdate()->findOrFail($materialId);
                $material->increment('current_stock', $quantity);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'material_id' => $material->id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $quantity * $unitCost,
                ]);
                StockMovement::create([
                    'material_id' => $material->id,
                    'movement_type' => 'purchase_in',
                    'quantity' => $quantity,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'remarks' => 'Direct purchase received',
                ]);
                DB::table('supplier_material')->upsert([
                    ['supplier_id' => $purchase->supplier_id, 'material_id' => $material->id, 'last_unit_cost' => $unitCost, 'created_at' => now(), 'updated_at' => now()],
                ], ['supplier_id', 'material_id'], ['last_unit_cost', 'updated_at']);
            }
            if ($purchaseOrder) {
                $ordered = $purchaseOrder->items->sum('quantity_ordered');
                $received = $purchaseOrder->purchases()->with('items')->get()->flatMap->items->sum('quantity_received');
                $purchaseOrder->update(['status' => $received >= $ordered ? 'fulfilled' : 'partially_fulfilled']);
            }
        });

        return to_route('purchases')->with('success', 'Purchase received successfully.');
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

                StockMovement::where('reference_type', 'purchase')->where('reference_id', $purchase->id)->delete();
            }

            if ($purchase->po_id) {
                $purchaseOrder = PurchaseOrder::with('items')->find($purchase->po_id);
                if ($purchaseOrder && in_array($purchaseOrder->status, ['fulfilled', 'partially_fulfilled'], true)) {
                    $purchaseOrder->update(['status' => 'approved']);
                }
            }

            $purchase->items()->delete();
            $purchase->delete();
        });

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return to_route('purchases')->with('success', 'Purchase deleted and stock reversed.');
    }
}
