<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'package_id',
        'amount',
        'tax_amount',
        'paid_amount',
        'remaining_amount',
        'description',
        'status',
        'due_date',
        'paid_date',
        'paid_at',
        'invoice_number',
        'invoice_type',
        'is_installment',
        'installment_number',
        'total_installments',
        'parent_invoice_id',
        'payment_gateway',
        'payment_order_id',
        'transaction_id',
        'payment_method',
        'payment_reference',
        'payment_url',
        'collected_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'integer',
        'tax_amount' => 'integer',
        'paid_amount' => 'integer',
        'remaining_amount' => 'integer',
        'is_installment' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-set remaining_amount when creating
        static::creating(function ($invoice) {
            if (!$invoice->remaining_amount) {
                $invoice->remaining_amount = $invoice->amount + ($invoice->tax_amount ?? 0);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function parentInvoice()
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function installments()
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    public function installmentPlan()
    {
        return $this->hasOne(InstallmentPlan::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount + ($this->tax_amount ?? 0);
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPartiallyPaid()
    {
        return $this->paid_amount > 0 && $this->paid_amount < $this->total_amount;
    }

    public function isOverdue()
    {
        return $this->status === 'unpaid' && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the remaining balance to be paid
     */
    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    /**
     * Record a payment against this invoice
     */
    public function recordPayment($amount, $paymentMethod = 'cash', $collectorId = null, $notes = null)
    {
        $customer = $this->customer;
        $balanceBefore = $customer->total_debt;

        // Create payment record
        $payment = Payment::create([
            'invoice_id' => $this->id,
            'collector_id' => $collectorId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'paid_at' => now(),
            'reference_number' => 'PAY-' . date('YmdHis') . '-' . $this->id,
        ]);

        // Update invoice paid amount
        $this->paid_amount += $amount;
        $this->remaining_amount = max(0, $this->total_amount - $this->paid_amount);

        // Check if fully paid
        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
            $this->paid_date = now();
            $this->paid_at = now();
        }

        $this->save();

        // Update customer debt
        $customer->total_debt = max(0, $customer->total_debt - $amount);
        $customer->last_payment_date = now();
        $customer->updateUnpaidInvoicesCount();
        $customer->save();

        // Record payment history
        PaymentHistory::create([
            'customer_id' => $customer->id,
            'invoice_id' => $this->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'type' => 'payment',
            'balance_before' => $balanceBefore,
            'balance_after' => $customer->total_debt,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);

        return $payment;
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Scope for non-installment invoices
     */
    public function scopeNotInstallment($query)
    {
        return $query->where('is_installment', false);
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber($prefix = 'INV')
    {
        $lastInvoice = static::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? (int)substr($lastInvoice->invoice_number, -5) + 1 : 1;

        return $prefix . '-' . now()->format('Ym') . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
