<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        return view('expenses', ['title' => 'Expenses', 'expenses' => Expense::with('purchase')->latest('expense_date')->limit(100)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['description' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'gt:0'], 'expense_date' => ['required', 'date'], 'purchase_id' => ['nullable', 'exists:purchases,id']]);
        Expense::create($validated);
        return to_route('expenses')->with('success', 'Expense recorded.');
    }

    public function transfer(Expense $expense): RedirectResponse
    {
        $expense->update(['transferred_to_sales' => ! $expense->transferred_to_sales]);
        return to_route('expenses')->with('success', 'Expense transfer status updated.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate(['description' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'gt:0'], 'expense_date' => ['required', 'date'], 'purchase_id' => ['nullable', 'exists:purchases,id']]);
        $expense->update($validated);
        return to_route('expenses')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();
        return to_route('expenses')->with('success', 'Expense deleted successfully.');
    }
}
