<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory ,SoftDeletes;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'lname',
        'city',
        'address',
        'phone',
        'image',
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}