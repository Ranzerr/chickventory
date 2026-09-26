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
     * Get all inventory transactions for this product.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'product_id', 'product_id');
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
        )->withPivot('quantity_required')->withTimestamps();
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
     * Falls back to 'current_stock' if no recipe materials are assigned.
     */
    public function availableStock(): int
    {
        if ($this->recipeMaterials->isEmpty()) {
            return (int) floor((float) $this->current_stock);
        }

        $possiblePortions = $this->recipeMaterials
            ->map(function ($material) {
                $required = (float) $material->pivot->quantity_required;
                if ($required <= 0) {
                    return null;
                }

                return floor((float) $material->current_stock / $required);
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