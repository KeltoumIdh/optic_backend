<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'cart_id',
        'payment_method',
        'date_debut_credit',
        'date_fin_credit',
        'reference_credit',
        'payment_status',
        'order_status',
        'is_credit',
        'delivery_date',
        'total_price',
        'paid_price',
        'remain_price',
        'notes',
        'order_date',
    ];

    protected $casts = [
        'date_debut_credit' => 'date',
        'date_fin_credit' => 'date',
        'delivery_date' => 'date',
        'order_date' => 'datetime',
        'is_credit' => 'boolean',
        'total_price' => 'decimal:2',
        'paid_price' => 'decimal:2',
        'remain_price' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}