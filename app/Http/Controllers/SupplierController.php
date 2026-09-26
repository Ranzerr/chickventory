<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Clear active metrics caches across dashboard and navigation badges.
     */
    private function clearMetricsCache(): void
    {
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('dashboard.data.v1');
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $suppliers = Supplier::withCount('products')
            ->select(['id', 'name', 'contact_person', 'phone', 'email', 'requires_po', 'status'])
            ->where('status', 'active')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('suppliers', [
            'title' => 'Suppliers',
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'requires_po' => ['nullable', 'boolean'],
        ]);

        Supplier::create($validated + [
            'status' => 'active',
            'requires_po' => $request->boolean('requires_po'),
        ]);

        $this->clearMetricsCache();

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

        $this->clearMetricsCache();

        return to_route('suppliers')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['status' => 'inactive']);

        $this->clearMetricsCache();

        return to_route('suppliers')->with('success', 'Supplier deleted successfully.');
    }
}