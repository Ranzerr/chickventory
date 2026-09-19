<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = ['po_number', 'supplier_id', 'created_by', 'order_date', 'status'];
    protected function casts(): array { return ['order_date' => 'date']; }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function purchases(): HasMany { return $this->hasMany(Purchase::class, 'po_id'); }
}
