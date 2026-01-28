<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherPricing extends Model
{
    protected $table = 'voucher_pricing';
    
    protected $fillable = [
        'package_name',
        'customer_price',
        'agent_price',
        'commission_amount',
        'duration',
        'description',
        'is_active',
    ];

    protected $casts = [
        'customer_price' => 'decimal:2',
        'agent_price' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];
}
