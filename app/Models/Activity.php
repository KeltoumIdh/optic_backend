<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        "user",
        "type",
        "details"
    ];

    protected $casts = [
        'details' => 'json',
    ];


    public function User()
    {
        return $this->hasOne(User::class, 'id', 'user');
    }
}
