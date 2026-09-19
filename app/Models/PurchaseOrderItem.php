<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'material_id', 'quantity_ordered', 'unit_price'];
    protected function casts(): array { return ['quantity_ordered' => 'decimal:2', 'unit_price' => 'decimal:2']; }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function material(): BelongsTo { return $this->belongsTo(RawMaterial::class, 'material_id'); }
}
