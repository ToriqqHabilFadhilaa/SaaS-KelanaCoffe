<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KASIR = 'kasir';

    public const ROLE_INVENTORY = 'inventory';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ---- Relationships ----

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Role helpers (dipakai di Policy/Middleware, hindari string compare berulang) ----

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function isInventory(): bool
    {
        return $this->role === self::ROLE_INVENTORY;
    }
}
