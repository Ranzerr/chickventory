<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['external_order_id', 'product_id', 'quantity', 'order_date', 'source_system'];
    protected function casts(): array
    {
        return ['order_date' => 'datetime'];
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
