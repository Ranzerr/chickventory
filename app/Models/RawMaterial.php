<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    protected $fillable = ['material_code', 'name', 'unit', 'current_stock', 'minimum_stock', 'status'];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:4', 'minimum_stock' => 'decimal:4'];
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withPivot('last_unit_cost')->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_recipes', 'material_id', 'product_id')->withPivot('quantity_required')->withTimestamps();
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'material_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'material_id');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock < $this->minimum_stock;
    }
}
