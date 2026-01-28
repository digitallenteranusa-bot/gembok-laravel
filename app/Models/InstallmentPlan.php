<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'total_amount',
        'installment_amount',
        'number_of_installments',
        'paid_installments',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'installment_amount' => 'integer',
        'remaining_amount' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installmentInvoices()
    {
        return $this->invoice->installments();
    }

    /**
     * Check if plan is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if plan is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->number_of_installments == 0) {
            return 0;
        }
        return round(($this->paid_installments / $this->number_of_installments) * 100, 1);
    }

    /**
     * Get next installment due date
     */
    public function getNextDueDateAttribute()
    {
        $nextInvoice = Invoice::where('parent_invoice_id', $this->invoice_id)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        return $nextInvoice?->due_date;
    }

    /**
     * Record installment payment
     */
    public function recordPayment($amount)
    {
        $this->paid_installments++;
        $this->remaining_amount = max(0, $this->remaining_amount - $amount);

        if ($this->remaining_amount <= 0 || $this->paid_installments >= $this->number_of_installments) {
            $this->status = 'completed';
        }

        $this->save();

        // Update customer
        $this->customer->has_installment_plan = $this->status === 'active';
        $this->customer->save();

        return $this;
    }

    /**
     * Cancel the plan
     */
    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: " . $reason;
        }
        $this->save();

        // Update customer
        $this->customer->has_installment_plan = false;
        $this->customer->save();

        return $this;
    }

    /**
     * Mark as defaulted (customer didn't pay)
     */
    public function markAsDefaulted()
    {
        $this->status = 'defaulted';
        $this->save();

        // Update customer
        $this->customer->has_installment_plan = false;
        $this->customer->recalculateDebt();

        return $this;
    }

    /**
     * Generate installment invoices
     */
    public function generateInstallmentInvoices()
    {
        $invoices = [];
        $dueDate = $this->start_date->copy();

        for ($i = 1; $i <= $this->number_of_installments; $i++) {
            $invoice = Invoice::create([
                'customer_id' => $this->customer_id,
                'package_id' => $this->invoice->package_id,
                'amount' => $this->installment_amount,
                'tax_amount' => 0,
                'remaining_amount' => $this->installment_amount,
                'description' => "Cicilan {$i}/{$this->number_of_installments} - " . $this->invoice->description,
                'status' => 'unpaid',
                'due_date' => $dueDate,
                'invoice_number' => Invoice::generateInvoiceNumber('CIC'),
                'invoice_type' => 'installment',
                'is_installment' => true,
                'installment_number' => $i,
                'total_installments' => $this->number_of_installments,
                'parent_invoice_id' => $this->invoice_id,
            ]);

            $invoices[] = $invoice;
            $dueDate->addMonth();
        }

        return $invoices;
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'defaulted' => 'Gagal Bayar',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'blue',
            'completed' => 'green',
            'cancelled' => 'gray',
            'defaulted' => 'red',
            default => 'gray',
        };
    }
}
