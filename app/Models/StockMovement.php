<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const REASON_PURCHASE = 'purchase';

    public const REASON_SALE = 'sale';

    public const REASON_DAMAGE = 'damage';

    public const REASON_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'type',
        'reason',
        'qty',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }

    // ---- Relationships ----

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
