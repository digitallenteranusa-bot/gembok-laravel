<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class BillingReport extends Command
{
    protected $signature = 'billing:report {--period=daily : Report period (daily, weekly, monthly)} {--send : Send report via WhatsApp}';
    protected $description = 'Generate billing report summary';

    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $period = $this->option('period');
        $send = $this->option('send');

        $startDate = match($period) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::today(),
        };
        $endDate = Carbon::now();

        $this->info("Generating {$period} billing report...");
        $this->info("Period: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");
        $this->newLine();

        // Collect stats
        $totalRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('amount');

        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->count();

        $unpaidInvoices = Invoice::where('status', 'unpaid')->count();
        
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::today())
            ->count();

        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $activeCustomers = Customer::where('status', 'active')->count();
        $suspendedCustomers = Customer::where('status', 'suspended')->count();

        $overdueAmount = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::today())
            ->sum('amount');

        // Display report
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.')],
                ['Invoice Terbayar', $paidInvoices],
                ['Invoice Belum Bayar', $unpaidInvoices],
                ['Invoice Overdue', $overdueInvoices],
                ['Total Piutang Overdue', 'Rp ' . number_format($overdueAmount, 0, ',', '.')],
                ['Pelanggan Baru', $newCustomers],
                ['Pelanggan Aktif', $activeCustomers],
                ['Pelanggan Suspended', $suspendedCustomers],
            ]
        );

        // Send via WhatsApp if requested
        if ($send) {
            $adminPhone = config('services.whatsapp.admin_phone');
            
            if ($adminPhone) {
                $message = "📊 *LAPORAN BILLING " . strtoupper($period) . "*\n";
                $message .= "Periode: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}\n\n";
                $message .= "💰 Pendapatan: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n";
                $message .= "✅ Invoice Terbayar: {$paidInvoices}\n";
                $message .= "⏳ Invoice Belum Bayar: {$unpaidInvoices}\n";
                $message .= "⚠️ Invoice Overdue: {$overdueInvoices}\n";
                $message .= "💸 Piutang Overdue: Rp " . number_format($overdueAmount, 0, ',', '.') . "\n\n";
                $message .= "👥 Pelanggan Aktif: {$activeCustomers}\n";
                $message .= "🚫 Pelanggan Suspended: {$suspendedCustomers}\n";
                $message .= "🆕 Pelanggan Baru: {$newCustomers}\n\n";
                $message .= "Generated: " . now()->format('d/m/Y H:i');

                $result = $this->whatsapp->sendMessage($adminPhone, $message);
                
                if ($result['success'] ?? false) {
                    $this->info('Report sent via WhatsApp');
                } else {
                    $this->error('Failed to send report via WhatsApp');
                }
            } else {
                $this->warn('Admin phone not configured. Set WHATSAPP_ADMIN_PHONE in .env');
            }
        }

        return Command::SUCCESS;
    }
}
