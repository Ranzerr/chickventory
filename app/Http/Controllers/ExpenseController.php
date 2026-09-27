<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::latest('expense_date')
            ->paginate(15);

        $totalSales = DB::table('sales')
            ->where('status', 'Completed')
            ->sum('total_amount');

        $totalExpenses = Expense::sum('amount');

        $transferredExpenses = Expense::where('transferred_to_sales', true)
            ->sum('amount');

        $netSales = $totalSales - $transferredExpenses;

        return view('expenses', [
            'title' => 'Expenses & Sales Integration',
            'expenses' => $expenses,
            'totalSales' => $totalSales,
            'totalExpenses' => $totalExpenses,
            'transferredExpenses' => $transferredExpenses,
            'netSales' => $netSales,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
        ]);

        // Auto-generate external_expense_id reference (e.g. EXP-20260926-AB12)
        $validated['external_expense_id'] = 'EXP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        Expense::create($validated);

        return to_route('expenses')->with('success', 'Expense recorded successfully.');
    }

    public function transfer(Expense $expense): RedirectResponse
    {
        $expense->update([
            'transferred_to_sales' => ! $expense->transferred_to_sales,
        ]);

        $message = $expense->transferred_to_sales 
            ? 'Expense transferred to Sales System.' 
            : 'Expense removed from Sales System transfer.';

        return to_route('expenses')->with('success', $message);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
        ]); 

        $expense->update($validated);

        return to_route('expenses')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return to_route('expenses')->with('success', 'Expense deleted successfully.');
    }
}