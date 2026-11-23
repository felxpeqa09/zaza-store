<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'referencia',
        'estado',
        'medio_pago',
        'forma_pago',
        'fecha_pago',
        'entrega_estimada',
        'total',
        'datos_envio',
        'stock_reserved_until',
        'provider_preference_id',
        'checkout_token',
        'checkout_url',
        'approved_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'datetime',
            'entrega_estimada' => 'date',
            'total' => 'integer',
            'datos_envio' => 'array',
            'stock_reserved_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
