<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_nik',
        'latitude',
        'longitude',
        'package_id',
        'connection_type',
        'package_price',
        'installation_fee',
        'discount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'payment_url',
        'paid_at',
        'status',
        'installation_date',
        'installation_time',
        'technician_id',
        'customer_notes',
        'admin_notes',
        'customer_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'installation_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $lastOrder = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastOrder ? (int)substr($lastOrder->order_number, -4) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => ['color' => 'yellow', 'label' => 'Menunggu'],
            'confirmed' => ['color' => 'blue', 'label' => 'Dikonfirmasi'],
            'scheduled' => ['color' => 'purple', 'label' => 'Dijadwalkan'],
            'installing' => ['color' => 'cyan', 'label' => 'Pemasangan'],
            'completed' => ['color' => 'green', 'label' => 'Selesai'],
            'cancelled' => ['color' => 'red', 'label' => 'Dibatalkan'],
            default => ['color' => 'gray', 'label' => ucfirst($this->status)],
        };
    }

    public function getPaymentStatusBadgeAttribute()
    {
        return match($this->payment_status) {
            'pending' => ['color' => 'yellow', 'label' => 'Belum Bayar'],
            'paid' => ['color' => 'green', 'label' => 'Lunas'],
            'failed' => ['color' => 'red', 'label' => 'Gagal'],
            'expired' => ['color' => 'gray', 'label' => 'Kadaluarsa'],
            default => ['color' => 'gray', 'label' => ucfirst($this->payment_status)],
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }
}
