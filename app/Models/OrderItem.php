<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_order_id',
        'product_id',
        'quantity',
        'order_date',
        'source_system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'order_date' => 'datetime',
        ];
    }

    /**
     * Get the product associated with the order item.
     * Explicitly map foreign key 'product_id' to owner key 'product_id' on Product model.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Scope a query to sort orders by latest order date.
     */
    public function scopeLatestOrders(Builder $query): Builder
    {
        return $query->orderByDesc('order_date');
    }

    /**
     * Model Boot Hook: Automatically deduct finished product stock and log
     * an automatic Stock Out inventory transaction upon order creation.
     */
    protected static function booted(): void
    {
        static::created(function (OrderItem $item) {
            if ($item->product) {
                // 1. Deduct finished product current_stock
                $item->product->decrement('current_stock', $item->quantity);

                // 2. Automatically record Stock Out transaction
                InventoryTransaction::create([
                    'transaction_code' => 'ORD-TXN-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
                    'product_id'       => $item->product_id,
                    'reference'        => $item->external_order_id ?: ('ORDER #' . $item->id),
                    'type'             => 'stock_out',
                    'quantity'         => $item->quantity,
                    'source'           => $item->source_system ? title_case($item->source_system) : 'Ordering System',
                    'status'           => 'completed',
                    'occurred_at'      => $item->order_date ?: now(),
                ]);
            }
        });
    }
}