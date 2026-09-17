<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'name',
        'sku',
        'unit',
        'stock',
        'minimum_stock',
        'cost_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
        ];
    }

    // ---- Relationships ----

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('qty_needed')
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Scopes ----

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock');
    }

    // ---- Domain logic ----

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }
}
