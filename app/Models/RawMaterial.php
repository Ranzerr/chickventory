<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'material_code',
        'name',
        'unit',
        'current_stock',
        'minimum_stock',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
        ];
    }

    /**
     * Get the suppliers that supply this raw material.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('last_unit_cost')
            ->withTimestamps();
    }

    /**
     * Get the products that use this raw material in their recipe.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_recipes', 'material_id', 'product_id')
            ->withPivot('quantity_required')
            ->withTimestamps();
    }

    /**
     * Get all purchase items associated with this raw material.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'material_id');
    }

    /**
     * Get all stock movements for this raw material.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'material_id');
    }

    /**
     * Check if current stock is below minimum threshold.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock < $this->minimum_stock;
    }

    /**
     * Scope a query to only include active materials.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to retrieve low stock materials at the database level.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereColumn('current_stock', '<', 'minimum_stock');
    }
}