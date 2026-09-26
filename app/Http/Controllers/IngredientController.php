<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IngredientController extends Controller
{
    /**
     * Clear all cached metrics shared across dashboard and navigation badges.
     */
    private function clearDashboardCache(): void
    {
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('dashboard.data.v1');
        Cache::forget('shared.low_stock_count.v1');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_code' => ['required', 'string', 'max:50', 'unique:raw_materials,material_code'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
        ]);

        RawMaterial::create($validated + ['status' => 'active']);

        $this->clearDashboardCache();

        return redirect()->route('products', ['tab' => 'ingredients'])
            ->with('success', 'Ingredient added successfully.');
    }

    public function update(Request $request, RawMaterial $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'material_code' => ['required', 'string', 'max:50', 'unique:raw_materials,material_code,'.$ingredient->id],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $ingredient->update($validated);

        $this->clearDashboardCache();

        return redirect()->route('products', ['tab' => 'ingredients'])
            ->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(RawMaterial $ingredient): RedirectResponse
    {
        $ingredient->update(['status' => 'inactive']);

        $this->clearDashboardCache();

        return redirect()->route('products', ['tab' => 'ingredients'])
            ->with('success', 'Ingredient deleted successfully.');
    }
}