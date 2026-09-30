<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    private function clearDashboardCache(): void
    {
        Cache::forget('dashboard.data.v1');
        Cache::forget('dashboard.data.v2');
        Cache::forget('dashboard.data.v3');
        Cache::forget('dashboard.data.v4');
        Cache::forget('dashboard.data.v5');
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('shared.low_stock_count.v1');
    }

    public function index(): View
    {
        return view('expenses', [
            'expenses' => Expense::with(['purchase.supplier', 'purchase.items.material:id,name,unit'])
                ->latest('expense_date')
                ->paginate(15),
            'materials' => RawMaterial::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'isAdmin' => auth()->user()?->isAdmin() ?? true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id'         => ['required', 'exists:suppliers,id'],
            'expense_date'        => ['required', 'date'],
            'description'         => ['nullable', 'string', 'max:255'],
            'material_id'         => ['required', 'array', 'min:1'],
            'material_id.*'       => ['required', 'exists:raw_materials,id'],
            'quantity_received'   => ['required', 'array', 'min:1'],
            'quantity_received.*' => ['required', 'numeric', 'gt:0'],
            'unit_cost'           => ['required', 'array', 'min:1'],
            'unit_cost.*'         => ['required', 'numeric', 'gte:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            $totalExpenseCost = 0;

            // 1. Create Purchase Parent Header (Stores supplier_id)
            $purchase = Purchase::create([
                'supplier_id'   => $validated['supplier_id'],
                'purchase_type' => 'direct',
                'received_by'   => auth()->id(),
                'purchase_date' => $validated['expense_date'],
            ]);

            // 2. Loop through materials, create PurchaseItems & update stock / WAC
            foreach ($validated['material_id'] as $index => $materialId) {
                $qty      = (float) $validated['quantity_received'][$index];
                $unitCost = (float) $validated['unit_cost'][$index];
                $subtotal = $qty * $unitCost;
                $totalExpenseCost += $subtotal;

                $material = RawMaterial::lockForUpdate()->findOrFail($materialId);

                // Recalculate Weighted Average Cost (WAC)
                $currentStock = (float) $material->current_stock;
                $currentCost  = (float) ($material->unit_cost ?? 0);
                $newStock     = $currentStock + $qty;

                $newWac = $newStock > 0 
                    ? (($currentStock * $currentCost) + ($qty * $unitCost)) / $newStock 
                    : $unitCost;

                $material->update([
                    'current_stock' => $newStock,
                    'unit_cost'     => $newWac,
                ]);

                // Create individual PurchaseItem entry
                $purchaseItem = PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'material_id'       => $material->id,
                    'quantity_received' => $qty,
                    'unit_cost'         => $unitCost,
                    'subtotal'          => $subtotal,
                ]);

                StockMovement::create([
                    'material_id'    => $material->id,
                    'movement_type'  => 'purchase_in',
                    'quantity'       => $qty,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase->id,
                    'remarks'        => 'Supply purchase expense #' . $purchase->id,
                ]);
            }

            $supplier = Supplier::find($validated['supplier_id']);

            // 3. Create Expense entry (Excludes 'supplier_id' which is stored on $purchase)
            Expense::create([
                'purchase_id'          => $purchase->id, // References the parent purchase
                'purchase_item_id'     => $purchaseItem->id, // References the purchase item
                'external_expense_id'  => 'EXP-SUPPLY-' . strtoupper(Str::random(8)),
                'description'          => $validated['description'] ?? ('Supply Purchase: ' . ($supplier->name ?? 'Supplier')),
                'category'             => 'Supply Expense',
                'amount'               => $totalExpenseCost,
                'expense_date'         => $validated['expense_date'],
                'source_system'        => 'ChickyVentory',
                'transferred_to_sales' => true,
                'sync_status'          => 'Synced',
            ]);
        });

        $this->clearDashboardCache();

        return back()->with('success', 'Supply expense recorded, material inventory updated, and purchase breakdown linked successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense): void {
            if ($expense->purchase_id) {
                $purchase = Purchase::with('items')->find($expense->purchase_id);

                if ($purchase) {
                    foreach ($purchase->items as $item) {
                        $material = RawMaterial::lockForUpdate()->find($item->material_id);
                        if ($material) {
                            $material->update(['current_stock' => max(0, $material->current_stock - $item->quantity_received)]);
                        }
                    }

                    StockMovement::where('reference_type', 'purchase')
                        ->where('reference_id', $purchase->id)
                        ->delete();

                    $purchase->items()->delete();
                    $purchase->delete();
                }
            }

            $expense->delete();
        });

        $this->clearDashboardCache();

        return back()->with('success', 'Supply expense deleted and inventory stock reversed.');
    }
}