<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendOverdueCustomers extends Command
{
    protected $signature = 'billing:suspend-overdue
                            {--days=7 : Days after due date for single invoice}
                            {--min-invoices=3 : Minimum unpaid invoices to trigger isolation}
                            {--dry-run : Preview without making changes}
                            {--recalculate : Recalculate all customer debts first}';

    protected $description = 'Suspend/isolate customers with overdue invoices or 3+ unpaid invoices';

    protected $mikrotik;
    protected $whatsapp;

    public function __construct(MikrotikService $mikrotik, WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->mikrotik = $mikrotik;
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $days = $this->option('days');
        $minInvoices = $this->option('min-invoices');
        $dryRun = $this->option('dry-run');
        $recalculate = $this->option('recalculate');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Recalculate all debts if requested
        if ($recalculate) {
            $this->info('Recalculating customer debts...');
            Customer::all()->each(function ($customer) {
                $customer->recalculateDebt();
            });
            $this->info('Debt recalculation completed.');
        }

        $this->newLine();
        $this->info("=== SUSPENSION CRITERIA ===");
        $this->info("1. Customers with {$minInvoices}+ unpaid invoices (non-installment)");
        $this->info("2. Customers with invoices overdue more than {$days} days");
        $this->info("3. EXCEPTION: Customers with active installment plan are SKIPPED");
        $this->newLine();

        $suspended = 0;
        $notified = 0;
        $skipped = 0;
        $errors = 0;

        // ==========================================
        // CRITERIA 1: 3 or more unpaid invoices
        // ==========================================
        $this->info("Checking customers with {$minInvoices}+ unpaid invoices...");

        $customersWithMultipleUnpaid = Customer::where('status', 'active')
            ->where('has_installment_plan', false) // Skip customers with installment
            ->where('unpaid_invoices_count', '>=', $minInvoices)
            ->with('package')
            ->get();

        $this->info("Found {$customersWithMultipleUnpaid->count()} customers with {$minInvoices}+ unpaid invoices.");

        foreach ($customersWithMultipleUnpaid as $customer) {
            // Double check - count actual unpaid non-installment invoices
            $actualUnpaidCount = $customer->invoices()
                ->where('status', 'unpaid')
                ->where('is_installment', false)
                ->count();

            if ($actualUnpaidCount < $minInvoices) {
                // Update the cached count
                $customer->unpaid_invoices_count = $actualUnpaidCount;
                $customer->save();
                continue;
            }

            $result = $this->suspendCustomer(
                $customer,
                "Hutang {$actualUnpaidCount} invoice",
                $dryRun
            );

            if ($result['success']) {
                $suspended++;
                if ($result['notified']) $notified++;
            } elseif ($result['skipped']) {
                $skipped++;
            } else {
                $errors++;
            }
        }

        // ==========================================
        // CRITERIA 2: Single invoice overdue > X days
        // ==========================================
        $cutoffDate = now()->subDays($days)->toDateString();
        $this->newLine();
        $this->info("Checking customers with invoices overdue since {$cutoffDate}...");

        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('is_installment', false) // Exclude installment invoices
            ->whereDate('due_date', '<', $cutoffDate)
            ->with(['customer.package'])
            ->get()
            ->unique('customer_id');

        $this->info("Found {$overdueInvoices->count()} customers with overdue invoices.");

        foreach ($overdueInvoices as $invoice) {
            $customer = $invoice->customer;

            if (!$customer || $customer->status === 'suspended') {
                continue;
            }

            // Skip if has installment plan
            if ($customer->has_installment_plan) {
                $this->line("<fg=yellow>SKIP:</> {$customer->name} - Has active installment plan");
                $skipped++;
                continue;
            }

            $daysOverdue = $invoice->due_date->diffInDays(now());
            $result = $this->suspendCustomer(
                $customer,
                "Invoice overdue {$daysOverdue} hari",
                $dryRun
            );

            if ($result['success']) {
                $suspended++;
                if ($result['notified']) $notified++;
            } elseif ($result['skipped']) {
                $skipped++;
            } else {
                $errors++;
            }
        }

        // Summary
        $this->newLine();
        $this->info("=== SUSPENSION COMPLETED ===");
        $this->table(
            ['Action', 'Count'],
            [
                ['Suspended/Isolated', $suspended],
                ['Notified via WA', $notified],
                ['Skipped (installment/already suspended)', $skipped],
                ['Errors', $errors],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Suspend a single customer
     */
    protected function suspendCustomer(Customer $customer, string $reason, bool $dryRun): array
    {
        $result = ['success' => false, 'notified' => false, 'skipped' => false];

        // Skip if already suspended
        if ($customer->status === 'suspended') {
            $this->line("<fg=gray>SKIP:</> {$customer->name} - Already suspended");
            $result['skipped'] = true;
            return $result;
        }

        // Skip if has active installment plan
        if ($customer->has_installment_plan) {
            $this->line("<fg=yellow>SKIP:</> {$customer->name} - Has active installment plan");
            $result['skipped'] = true;
            return $result;
        }

        $this->line("<fg=red>SUSPEND:</> {$customer->name} ({$customer->pppoe_username}) - {$reason}");

        if ($dryRun) {
            $result['success'] = true;
            return $result;
        }

        try {
            // Disconnect from Mikrotik
            if ($customer->pppoe_username && $this->mikrotik->isConnected()) {
                try {
                    $this->mikrotik->disconnectPPPoE($customer->pppoe_username);
                } catch (\Exception $e) {
                    Log::warning("Failed to disconnect PPPoE for {$customer->pppoe_username}: " . $e->getMessage());
                }
            }

            // Isolate customer
            $customer->isolate($reason);

            Log::info('Customer isolated', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'reason' => $reason,
            ]);

            $result['success'] = true;

            // Send WhatsApp notification
            if ($customer->phone) {
                try {
                    $message = "Yth. *{$customer->name}*,\n\n";
                    $message .= "Layanan internet Anda telah *DIISOLIR* karena:\n";
                    $message .= "📋 {$reason}\n\n";
                    $message .= "Silakan segera melakukan pembayaran untuk mengaktifkan kembali layanan Anda.\n\n";
                    $message .= "Total hutang: *Rp " . number_format($customer->total_debt, 0, ',', '.') . "*\n\n";
                    $message .= "Untuk informasi lebih lanjut, hubungi customer service kami.\n\n";
                    $message .= "Terima kasih,\n";
                    $message .= "*" . companyName() . "*";

                    $waResult = $this->whatsapp->sendMessage($customer->phone, $message);
                    if ($waResult['success'] ?? false) {
                        $result['notified'] = true;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to send suspension notice to {$customer->phone}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to suspend customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
