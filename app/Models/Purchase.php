<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['po_id', 'purchase_type', 'supplier_id', 'received_by', 'purchase_date'];
    protected function casts(): array { return ['purchase_date' => 'date']; }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
}
