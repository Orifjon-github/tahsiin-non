<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'quantity',
        'price_per_item',
        'total_price',
        'delivery_date',
        'delivery_time_slot',
        'status',
        'confirmed_at',
        'completed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'price_per_item' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Buyurtmaga tegishli foydalanuvchi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Buyurtma raqamini avtomatik generatsiya qilish
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'TB-' . strtoupper(substr(uniqid(), -8));;
            }
        });
    }

    /**
     * Holat rangi (admin panel uchun)
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Holat matni
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Tayyorlanmoqda',
            'confirmed' => 'Tasdiqlangan',
            'completed' => 'Bajarilgan',
            'cancelled' => 'Bekor qilingan',
            default => 'Noma\'lum'
        };
    }
}
