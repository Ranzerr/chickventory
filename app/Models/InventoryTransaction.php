<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_code', 'product_id', 'reference', 'type', 'quantity', 'source', 'status', 'occurred_at'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
