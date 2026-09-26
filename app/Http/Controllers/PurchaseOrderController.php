<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('purchase-orders', [
            'title' => 'Purchase Orders',
            'purchaseOrders' => PurchaseOrder::with(['supplier:id,name', 'items.material:id,name,unit'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->latest('order_date')
                ->paginate(15),
            'suppliers' => Supplier::select(['id', 'name'])
                ->where('status', 'active')
                ->where('requires_po', true)
                ->orderBy('name')
                ->get(),
            'materials' => RawMaterial::select(['id', 'name', 'unit'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['required', 'exists:raw_materials,id'],
            'quantity_ordered' => ['required', 'array'],
            'quantity_ordered.*' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['required', 'numeric', 'gte:0'],
        ]);

        if (! Supplier::whereKey($validated['supplier_id'])->where('requires_po', true)->exists()) {
            return back()->withErrors(['supplier_id' => 'This supplier does not require a purchase order.'])->withInput();
        }

        DB::transaction(function () use ($validated): void {
            $po = PurchaseOrder::create([
                'created_by' => auth()->id(),
                'po_number' => 'PO-'.now()->format('YmdHis').'-'.str()->upper(str()->random(4)),
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'status' => 'draft',
            ]);

            $items = [];
            foreach ($validated['material_id'] as $index => $materialId) {
                $items[] = [
                    'material_id' => $materialId,
                    'quantity_ordered' => $validated['quantity_ordered'][$index],
                    'unit_price' => $validated['unit_price'][$index],
                ];
            }

            // Batch creation in a single query
            $po->items()->createMany($items);
        });

        return to_route('purchase-orders')->with('success', 'Purchase order created.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft purchase orders can be approved.']);
        }

        $purchaseOrder->update(['status' => 'approved']);

        return back()->with('success', "{$purchaseOrder->po_number} approved.");
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft purchase orders can be deleted.']);
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return to_route('purchase-orders')->with('success', "{$purchaseOrder->po_number} deleted.");
    }
}