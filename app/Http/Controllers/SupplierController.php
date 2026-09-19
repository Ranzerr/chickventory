<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliersQuery = Supplier::withCount('products')->where('status', 'active')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name');

        $suppliers = $suppliersQuery->get();

        return view('suppliers', ['title' => 'Suppliers', 'suppliers' => $suppliers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        Supplier::create($validated + ['status' => 'active']);
        cache()->forget('dashboard.metrics.v3');

        return to_route('suppliers')->with('success', 'Supplier added successfully.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'requires_po' => ['nullable', 'boolean'],
        ]);

        $supplier->update($validated + ['requires_po' => $request->boolean('requires_po')]);
        cache()->forget('dashboard.metrics.v3');

        return to_route('suppliers')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['status' => 'inactive']);
        cache()->forget('dashboard.metrics.v3');

        return to_route('suppliers')->with('success', 'Supplier deleted successfully.');
    }
}
