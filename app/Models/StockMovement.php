<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['material_id', 'movement_type', 'quantity', 'reference_type', 'reference_id', 'performed_by', 'remarks'];
    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function material(): BelongsTo { return $this->belongsTo(RawMaterial::class, 'material_id'); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}
