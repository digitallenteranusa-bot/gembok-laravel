<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'collector_id',
        'amount',
        'payment_method',
        'commission',
        'notes',
        'reference_number',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Invoice::class, 'id', 'id', 'invoice_id', 'customer_id');
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Get payment method label
     */
    public function getMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'collector' => 'Kolektor',
            'midtrans' => 'Midtrans',
            'xendit' => 'Xendit',
            'duitku' => 'Duitku',
            default => ucfirst($this->payment_method),
        };
    }
}
