<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'expenses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_id',
        'purchase_item_id',
        'external_expense_id',
        'supplier_id',
        'description',
        'category',
        'amount',
        'expense_date',
        'source_system',
        'transferred_to_sales',
        'sync_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'transferred_to_sales' => 'boolean',
        ];
    }

    /**
     * Get the purchase breakdown associated with this expense.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'id');
    }

    /**
     * Get the supplier associated with this supply expense.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Check if this expense is linked to a supply purchase order breakdown.
     */
    public function isPurchaseBreakdown(): bool
    {
        return $this->purchase_id !== null;
    }
    // In Expense.php
        public function purchaseItem()
        {
            return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
        }
}