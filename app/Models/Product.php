<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['product_code', 'name', 'category', 'supplier_id', 'current_stock', 'minimum_stock', 'unit', 'status'];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:2', 'minimum_stock' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function recipeMaterials(): BelongsToMany
    {
        return $this->belongsToMany(RawMaterial::class, 'product_recipes', 'product_id', 'material_id')->withPivot('quantity_required')->withTimestamps();
    }

    public function isLowStock(): bool
    {
        return $this->availableStock() < $this->minimum_stock;
    }

    public function availableStock(): int
    {
        if ($this->recipeMaterials->isEmpty()) {
            return 0;
        }

        return (int) $this->recipeMaterials
            ->map(fn ($material) => floor((float) $material->current_stock / (float) $material->pivot->quantity_required))
            ->min();
    }
}
