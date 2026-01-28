<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'speed',
        'mikrotik_profile',
        'hotspot_profile',
        'price',
        'tax_rate',
        'description',
        'is_active',
        'pppoe_profile',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'tax_rate' => 'float',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
