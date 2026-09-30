<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';

    protected $fillable = [
        'product_id',
        'material_id',
        'quantity_required',
        'unit',
        'conversion_factor',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:4',
            'conversion_factor' => 'decimal:6',
        ];
    }

    /**
     * Get the product this recipe item belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get the raw material used in this recipe item.
     */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'material_id', 'id');
    }

    /**
     * Accessor: Calculates line-item cost contribution for this ingredient.
     */
    public function getCalculatedCostAttribute(): float
    {
        if (!$this->rawMaterial) {
            return 0.0;
        }

        $usedInBaseUnit = (float) $this->quantity_required * (float) $this->conversion_factor;

        return $usedInBaseUnit * $this->rawMaterial->effective_unit_cost;
    }
}