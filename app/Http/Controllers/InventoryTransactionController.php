<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $type = $request->string('type')->trim()->toString();

        // 1. Finished Product Transactions (Manual / Direct Inventory Transactions)
        $productQuery = DB::table('inventory_transactions as it')
            ->leftJoin('products as p', 'it.product_id', '=', 'p.product_id')
            ->select([
                'it.transaction_code',
                'it.occurred_at',
                'it.reference',
                DB::raw("COALESCE(p.name, 'Unknown') as item_name"),
                'it.type',
                'it.quantity',
                DB::raw("COALESCE(p.unit, 'pcs') as unit"),
                'it.source',
                'it.status',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('it.transaction_code', 'like', "%{$search}%")
                      ->orWhere('it.reference', 'like', "%{$search}%")
                      ->orWhere('p.name', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                $query->where('it.type', $type);
            });

        // 2. Raw Material Movements (Stock In / Kitchen Usage)
        $movementQuery = DB::table('stock_movements as sm')
            ->leftJoin('raw_materials as rm', 'sm.material_id', '=', 'rm.id')
            ->select([
                DB::raw("CONCAT('MOV-', LPAD(sm.id, 6, '0')) as transaction_code"),
                'sm.created_at as occurred_at',
                'sm.remarks as reference',
                DB::raw("COALESCE(rm.name, 'Unknown') as item_name"),
                DB::raw("CASE WHEN sm.movement_type IN ('stock_in', 'purchase_in') THEN 'stock_in' ELSE 'stock_out' END as type"),
                'sm.quantity',
                DB::raw("COALESCE(rm.unit, 'pcs') as unit"),
                DB::raw("CASE WHEN sm.reference_type = 'purchase' THEN 'Purchasing' ELSE 'Manual Entry' END as source"),
                DB::raw("'completed' as status"),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sm.remarks', 'like', "%{$search}%")
                      ->orWhere('rm.name', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                if ($type === 'stock_in') {
                    $query->whereIn('sm.movement_type', ['stock_in', 'purchase_in']);
                } elseif ($type === 'stock_out') {
                    $query->where('sm.movement_type', 'usage_out');
                }
            });

        // 3. Automated Ordering System Integration (Stock Out directly from tbl_orders and tbl_order_items)
        $orderQuery = DB::table('tbl_order_items as toi')
            ->join('tbl_orders as o', 'toi.order_id', '=', 'o.order_id')
            ->leftJoin('products as p', 'toi.product_id', '=', 'p.product_id')
            ->select([
                DB::raw("CONCAT('ORD-TXN-', LPAD(toi.order_item_id, 6, '0')) as transaction_code"),
                'o.created_at as occurred_at',
                'o.order_number as reference',
                DB::raw("COALESCE(p.name, 'Unknown') as item_name"),
                DB::raw("'stock_out' as type"),
                'toi.quantity',
                DB::raw("COALESCE(p.unit, 'pcs') as unit"),
                DB::raw("'Ordering System' as source"),
                DB::raw("CASE WHEN o.payment_status = 'paid' THEN 'completed' ELSE 'pending' END as status"),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('o.order_number', 'like', "%{$search}%")
                      ->orWhere('p.name', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                if ($type === 'stock_in') {
                    $query->whereRaw('1 = 0'); // Exclude order stock-outs if stock_in is selected
                }
            });

        // 4. Combine all streams using UNION
        $unionQuery = $productQuery
            ->unionAll($movementQuery)
            ->unionAll($orderQuery);

        // 5. Paginate and cast dates
        $perPage = 20;
        $page = Paginator::resolveCurrentPage('page');

        $total = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->count();

        $results = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->orderByDesc('occurred_at')
            ->forPage($page, $perPage)
            ->get();

        // Ensure occurred_at is mapped to Carbon instances for safe ->format() calls in Blade
        $results->transform(function ($transaction) {
            $transaction->occurred_at = Carbon::parse($transaction->occurred_at);
            return $transaction;
        });

        $paginatedTransactions = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('inventory-transactions', [
            'title' => 'Inventory Transactions',
            'transactions' => $paginatedTransactions,
        ]);
    }
}