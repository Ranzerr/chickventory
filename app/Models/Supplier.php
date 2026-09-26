<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'status',
        'requires_po',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_po' => 'boolean',
        ];
    }

    /**
     * Get all products assigned to this supplier.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all raw materials supplied by this supplier.
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(RawMaterial::class)
            ->withPivot('last_unit_cost')
            ->withTimestamps();
    }

    /**
     * Get all purchase orders issued to this supplier.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Scope a query to only active suppliers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to suppliers that require purchase orders.
     */
    public function scopeRequiresPo(Builder $query): Builder
    {
        return $query->where('requires_po', true);
    }
}