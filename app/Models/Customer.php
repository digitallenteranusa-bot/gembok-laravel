<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'username',
        'pppoe_username',
        'pppoe_password',
        'static_ip',
        'mac_address',
        'name',
        'phone',
        'email',
        'address',
        'package_id',
        'status',
        'total_debt',
        'unpaid_invoices_count',
        'has_installment_plan',
        'last_payment_date',
        'isolated_at',
        'isolation_reason',
        'join_date',
    ];

    protected $casts = [
        'join_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'isolated_at' => 'datetime',
        'has_installment_plan' => 'boolean',
        'total_debt' => 'integer',
        'unpaid_invoices_count' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function unpaidInvoices()
    {
        return $this->hasMany(Invoice::class)->where('status', 'unpaid');
    }

    public function overdueInvoices()
    {
        return $this->hasMany(Invoice::class)
            ->where('status', 'unpaid')
            ->where('due_date', '<', now());
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function installmentPlans()
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function activeInstallmentPlan()
    {
        return $this->hasOne(InstallmentPlan::class)->where('status', 'active');
    }

    public function cableRoutes()
    {
        return $this->hasMany(CableRoute::class);
    }

    public function onuDevices()
    {
        return $this->hasMany(OnuDevice::class);
    }

    /**
     * Update unpaid invoices count
     */
    public function updateUnpaidInvoicesCount()
    {
        $this->unpaid_invoices_count = $this->invoices()
            ->where('status', 'unpaid')
            ->where('is_installment', false)
            ->count();

        return $this;
    }

    /**
     * Calculate total debt from unpaid invoices
     */
    public function calculateTotalDebt()
    {
        $this->total_debt = $this->invoices()
            ->where('status', 'unpaid')
            ->sum(\DB::raw('amount + COALESCE(tax_amount, 0) - COALESCE(paid_amount, 0)'));

        return $this;
    }

    /**
     * Recalculate all debt metrics
     */
    public function recalculateDebt()
    {
        $this->calculateTotalDebt();
        $this->updateUnpaidInvoicesCount();
        $this->has_installment_plan = $this->installmentPlans()->where('status', 'active')->exists();
        $this->save();

        return $this;
    }

    /**
     * Check if customer should be isolated (3+ unpaid invoices without installment plan)
     */
    public function shouldBeIsolated()
    {
        // Don't isolate if has active installment plan
        if ($this->has_installment_plan) {
            return false;
        }

        // Check unpaid invoices count (non-installment invoices only)
        $unpaidCount = $this->invoices()
            ->where('status', 'unpaid')
            ->where('is_installment', false)
            ->count();

        return $unpaidCount >= 3;
    }

    /**
     * Isolate/suspend customer due to debt
     */
    public function isolate($reason = 'Hutang 3 invoice atau lebih')
    {
        $this->status = 'suspended';
        $this->isolated_at = now();
        $this->isolation_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Reactivate customer
     */
    public function reactivate()
    {
        $this->status = 'active';
        $this->isolated_at = null;
        $this->isolation_reason = null;
        $this->save();

        return $this;
    }

    /**
     * Check if customer is isolated
     */
    public function isIsolated()
    {
        return $this->status === 'suspended' && $this->isolated_at !== null;
    }

    /**
     * Get debt aging report (berapa lama hutang)
     */
    public function getDebtAgingAttribute()
    {
        $aging = [
            'current' => 0,      // Not yet due
            '1-30' => 0,        // 1-30 days overdue
            '31-60' => 0,       // 31-60 days overdue
            '61-90' => 0,       // 61-90 days overdue
            'over_90' => 0,     // Over 90 days overdue
        ];

        $unpaidInvoices = $this->invoices()->where('status', 'unpaid')->get();

        foreach ($unpaidInvoices as $invoice) {
            $remaining = $invoice->remaining_balance;

            if (!$invoice->due_date || $invoice->due_date->isFuture()) {
                $aging['current'] += $remaining;
            } else {
                $daysOverdue = $invoice->due_date->diffInDays(now());

                if ($daysOverdue <= 30) {
                    $aging['1-30'] += $remaining;
                } elseif ($daysOverdue <= 60) {
                    $aging['31-60'] += $remaining;
                } elseif ($daysOverdue <= 90) {
                    $aging['61-90'] += $remaining;
                } else {
                    $aging['over_90'] += $remaining;
                }
            }
        }

        return $aging;
    }

    /**
     * Scope for customers with debt
     */
    public function scopeWithDebt($query)
    {
        return $query->where('total_debt', '>', 0);
    }

    /**
     * Scope for customers eligible for isolation
     */
    public function scopeEligibleForIsolation($query)
    {
        return $query->where('status', 'active')
            ->where('has_installment_plan', false)
            ->where('unpaid_invoices_count', '>=', 3);
    }
}
