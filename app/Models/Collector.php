<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collector extends Model
{
    protected $fillable = [
        'name',
        'username',
        'phone',
        'email',
        'commission_rate',
        'status',
        'password',
        'area',
        'user_id',
    ];

    protected $casts = [
        'commission_rate' => 'float',
    ];

    protected $hidden = [
        'password',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
