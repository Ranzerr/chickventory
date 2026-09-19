<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $productTransactions = InventoryTransaction::with('product')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('transaction_code', 'like', '%'.$request->string('search').'%')
                    ->orWhere('reference', 'like', '%'.$request->string('search').'%');
            }))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->get()
            ->map(fn (InventoryTransaction $transaction) => (object) [
                'transaction_code' => $transaction->transaction_code,
                'occurred_at' => $transaction->occurred_at,
                'reference' => $transaction->reference,
                'item_name' => $transaction->product?->name ?? 'Unknown',
                'type' => $transaction->type,
                'quantity' => $transaction->quantity,
                'unit' => $transaction->product?->unit ?? 'pcs',
                'source' => $transaction->source,
                'status' => $transaction->status,
            ]);

        $materialMovements = StockMovement::with('material')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';

                $query->where('remarks', 'like', $search)
                    ->orWhereHas('material', fn ($query) => $query->where('name', 'like', $search));
            }))
            ->when($request->filled('type'), function ($query) use ($request): void {
                $type = $request->string('type')->toString();

                if ($type === 'stock_in') {
                    $query->whereIn('movement_type', ['stock_in', 'purchase_in']);
                } elseif ($type === 'stock_out') {
                    $query->where('movement_type', 'usage_out');
                }
            })
            ->get()
            ->map(fn (StockMovement $movement) => (object) [
                'transaction_code' => 'MOV-'.str_pad((string) $movement->id, 6, '0', STR_PAD_LEFT),
                'occurred_at' => $movement->created_at,
                'reference' => $movement->remarks,
                'item_name' => $movement->material?->name ?? 'Unknown',
                'type' => in_array($movement->movement_type, ['stock_in', 'purchase_in'], true) ? 'stock_in' : 'stock_out',
                'quantity' => $movement->quantity,
                'unit' => $movement->material?->unit ?? 'pcs',
                'source' => $movement->reference_type === 'purchase' ? 'Purchasing' : 'Manual Entry',
                'status' => 'completed',
            ]);

        $transactions = $productTransactions->concat($materialMovements)
            ->sortByDesc(fn (object $transaction) => $transaction->occurred_at?->timestamp ?? 0)
            ->take(100)
            ->values();

        return view('inventory-transactions', ['title' => 'Inventory Transactions', 'transactions' => $transactions]);
    }
}
