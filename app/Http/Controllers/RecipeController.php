<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'material_id' => ['required', 'exists:raw_materials,id'],
            'quantity_required' => ['required', 'numeric', 'gt:0'],
        ]);
        $product->recipeMaterials()->syncWithoutDetaching([$validated['material_id'] => ['quantity_required' => $validated['quantity_required']]]);
        return back()->with('success', 'Recipe ingredient saved.');
    }

    public function destroy(Product $product, RawMaterial $material): RedirectResponse
    {
        $product->recipeMaterials()->detach($material->id);
        return back()->with('success', 'Recipe ingredient removed.');
    }
}
