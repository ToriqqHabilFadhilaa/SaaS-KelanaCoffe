<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ---- Relationships ----

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Resep: bahan apa saja + qty yang dibutuhkan untuk 1 unit produk ini
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('qty_needed')
            ->withTimestamps();
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    // ---- Scopes ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ---- Domain logic ----

    /**
     * Cek apakah stok bahan baku cukup untuk membuat produk ini sejumlah $qty.
     * Dipakai sebelum checkout, agar tidak sampai stok minus.
     */
    public function hasSufficientStock(int $qty = 1): bool
    {
        return $this->ingredients->every(
            fn (Ingredient $ingredient) => $ingredient->stock >= ($ingredient->pivot->qty_needed * $qty)
        );
    }
}
