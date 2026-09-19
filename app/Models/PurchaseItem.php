<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'material_id', 'quantity_received', 'unit_cost', 'subtotal'];
    protected function casts(): array { return ['quantity_received' => 'decimal:2', 'unit_cost' => 'decimal:2', 'subtotal' => 'decimal:2']; }
    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function material(): BelongsTo { return $this->belongsTo(RawMaterial::class, 'material_id'); }
}
