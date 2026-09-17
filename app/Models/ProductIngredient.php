<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductIngredient extends Model
{
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'qty_needed',
    ];

    protected function casts(): array
    {
        return [
            'qty_needed' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
