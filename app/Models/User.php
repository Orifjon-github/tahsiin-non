<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method static where(string $string, $username)
 * @method create(string[] $array)
 * @method updateOrCreate(array $array, array $array1)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'from_qr' => 'boolean',
    ];
    /**
     * Foydalanuvchining buyurtmalari
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Faol buyurtmalar
     */
    public function activeOrders()
    {
        return $this->orders()
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Oxirgi buyurtma
     */
    public function latestOrder()
    {
        return $this->orders()
            ->latest()
            ->first();
    }

    /**
     * To'liq ism
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
