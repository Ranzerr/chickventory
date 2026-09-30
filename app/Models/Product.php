<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Primary key for the products table in chicky_db.
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * Attributes mass assignable per active schema.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_code',
        'name',
        'price',
        'image',
        'image_mime_type',
        'category',
        'current_stock',
        'minimum_stock',
        'unit',
        'status',
    ];

    /**
     * Attribute casting configuration.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
        ];
    }

    /**
     * Direct relationship to ProductRecipe model.
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id', 'product_id');
    }

    /**
     * Get raw materials used in this product's recipe.
     */
    public function recipeMaterials(): BelongsToMany
    {
        return $this->belongsToMany(
            RawMaterial::class,
            'product_recipes',
            'product_id',  // FK on product_recipes
            'material_id', // FK on product_recipes
            'product_id',  // Owner Key on products
            'id'           // Owner Key on raw_materials
        )
        ->withPivot(['quantity_required', 'unit', 'conversion_factor'])
        ->withTimestamps();
    }

    /**
     * Get all inventory transactions for this product.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'product_id', 'product_id');
    }

    /**
     * Accessor: Dynamically aggregates total COGS based on current ingredient unit costs.
     */
    public function getCostToProduceAttribute(): float
    {
        // Use recipeMaterials pivot data as a fallback if recipes HasMany isn't loaded
        if ($this->relationLoaded('recipes') && $this->recipes->isNotEmpty()) {
            return (float) $this->recipes->sum(fn ($recipe) => $recipe->calculated_cost);
        }

        if ($this->recipeMaterials->isNotEmpty()) {
            return (float) $this->recipeMaterials->sum(function ($material) {
                $qty = (float) ($material->pivot->quantity_required ?? 0);
                $factor = (float) ($material->pivot->conversion_factor ?? 1.0);
                $unitCost = (float) ($material->effective_unit_cost ?? $material->unit_cost ?? 0);

                return ($qty * $factor) * $unitCost;
            });
        }

        return 0.0;
    }

    /**
     * Accessor: Profit margin in pesos per unit.
     */
    public function getProfitMarginAttribute(): float
    {
        return (float) $this->price - $this->cost_to_produce;
    }

    /**
     * Accessor: Gross profit margin percentage.
     */
    public function getProfitMarginPercentageAttribute(): float
    {
        if ((float) $this->price <= 0) {
            return 0.0;
        }

        return ($this->profit_margin / (float) $this->price) * 100;
    }

    /**
     * Check if product stock is below minimum threshold.
     */
    public function isLowStock(): bool
    {
        return $this->availableStock() <= (float) $this->minimum_stock;
    }

    /**
     * Calculate max batch availability based on recipe raw material stocks (bottleneck logic).
     * Takes unit conversion factors into account (e.g. 200g = 0.2kg).
     * Falls back to 'current_stock' if no recipe materials are assigned.
     */
    public function availableStock(): int
    {
        if ($this->recipeMaterials->isEmpty()) {
            return (int) floor((float) $this->current_stock);
        }

        $possiblePortions = $this->recipeMaterials
            ->map(function ($material) {
                $rawQtyRequired = (float) $material->pivot->quantity_required;
                $factor = (float) ($material->pivot->conversion_factor ?? 1.0);
                
                // Actual quantity required in base raw material unit
                $effectiveRequired = $rawQtyRequired * $factor;

                if ($effectiveRequired <= 0) {
                    return null;
                }

                return floor((float) $material->current_stock / $effectiveRequired);
            })
            ->filter(fn ($val) => $val !== null);

        if ($possiblePortions->isEmpty()) {
            return 0;
        }

        return (int) $possiblePortions->min();
    }

    /**
     * Scope query to only active/available products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'available']);
    }
}