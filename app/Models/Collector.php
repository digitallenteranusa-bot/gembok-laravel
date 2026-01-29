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

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get total collection amount for a period
     */
    public function getTotalCollection($startDate = null, $endDate = null)
    {
        $query = $this->payments();

        if ($startDate) {
            $query->where('paid_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('paid_at', '<=', $endDate);
        }

        return $query->sum('amount');
    }

    /**
     * Get total debt from assigned customers
     */
    public function getTotalCustomerDebt()
    {
        return $this->customers()->sum('total_debt');
    }
}
