<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_id',
        'purchase_type',
        'supplier_id',
        'received_by',
        'purchase_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
        ];
    }

    /**
     * Get the related purchase order (if PO-based).
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the supplier for this purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who received this purchase.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get line items in this purchase.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Scope query to direct purchases.
     */
    public function scopeDirect(Builder $query): Builder
    {
        return $query->where('purchase_type', 'direct');
    }

    /**
     * Scope query to PO-based purchases.
     */
    public function scopePoBased(Builder $query): Builder
    {
        return $query->where('purchase_type', 'po_based');
    }

    /**
     * Scope query to order by latest purchase date.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('purchase_date');
    }
}