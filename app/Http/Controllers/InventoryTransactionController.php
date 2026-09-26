<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $type = $request->string('type')->trim()->toString();

        // 1. Build Query for Product Transactions
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

        // 2. Build Query for Stock Movements
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

        // 3. Combine both tables at DB level using UNION
        $unionQuery = $productQuery->unionAll($movementQuery);

        // 4. Wrap Union Query to apply efficient DB sorting and pagination
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