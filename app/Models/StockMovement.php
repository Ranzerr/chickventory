<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'material_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'performed_by',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    /**
     * Get the raw material associated with this stock movement.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'material_id');
    }

    /**
     * Get the user who performed this stock movement.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope query to only stock-in movements.
     */
    public function scopeStockIn(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['stock_in', 'purchase_in']);
    }

    /**
     * Scope query to sort by latest created date.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }
}