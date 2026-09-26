<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_number',
        'supplier_id',
        'created_by',
        'order_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
        ];
    }

    /**
     * Get the supplier assigned to this purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get line items for this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get all purchases generated from this purchase order.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'po_id');
    }

    /**
     * Scope query to draft purchase orders.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope query to approved purchase orders.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope query to fulfilled or partially fulfilled orders.
     */
    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->whereIn('status', ['fulfilled', 'partially_fulfilled']);
    }

    /**
     * Scope query to sort by latest order date.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('order_date');
    }
}