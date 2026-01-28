<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'username',
        'name',
        'phone',
        'email',
        'password',
        'address',
        'balance',
        'commission_rate',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    protected $hidden = [
        'password',
    ];
}
