<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['purchase_id', 'description', 'amount', 'expense_date', 'transferred_to_sales'];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expense_date' => 'date', 'transferred_to_sales' => 'boolean'];
    }
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
