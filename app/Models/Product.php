<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory ,SoftDeletes;
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'name',
        'price',
        'quantity_available',
        'initial_quantity',
        'quantity_sold',
        'image',
        'status',
        'message',
    ];
}