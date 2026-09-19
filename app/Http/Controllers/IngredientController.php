<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
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

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'ingredients'])->with('success', 'Ingredient added successfully.');
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

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'ingredients'])->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(RawMaterial $ingredient): RedirectResponse
    {
        $ingredient->update(['status' => 'inactive']);

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'ingredients'])->with('success', 'Ingredient deleted successfully.');
    }
}

