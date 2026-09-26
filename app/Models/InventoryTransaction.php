<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'product_id',
        'reference',
        'type',
        'quantity',
        'source',
        'status',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Get the product associated with the transaction.
     * Arguments: (Target Model, Foreign Key in inventory_transactions, Primary Key in products)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}