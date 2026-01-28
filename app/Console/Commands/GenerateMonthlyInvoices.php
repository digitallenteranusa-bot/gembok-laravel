<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'billing:generate-invoices {--month= : Month (1-12)} {--year= : Year}';
    protected $description = 'Generate monthly invoices for all active customers';

    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;
        
        $this->info("Generating invoices for {$month}/{$year}...");

        $customers = Customer::where('status', 'active')
            ->whereNotNull('package_id')
            ->with('package')
            ->get();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            try {
                // Check if invoice already exists for this month
                $existingInvoice = Invoice::where('customer_id', $customer->id)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->first();

                if ($existingInvoice) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Generate invoice
                $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
                
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'customer_id' => $customer->id,
                    'package_id' => $customer->package_id,
                    'amount' => $customer->package->price ?? 0,
                    'due_date' => now()->setMonth($month)->setYear($year)->endOfMonth(),
                    'status' => 'unpaid',
                    'description' => "Tagihan Internet {$customer->package->name} - " . now()->setMonth($month)->format('F Y'),
                ]);

                // Send WhatsApp notification
                if ($customer->phone) {
                    $this->whatsapp->sendInvoiceNotification($customer, $invoice);
                }

                $generated++;
                Log::info('Invoice generated', ['invoice_id' => $invoice->id, 'customer_id' => $customer->id]);

            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to generate invoice', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Invoice generation completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Generated', $generated],
                ['Skipped (exists)', $skipped],
                ['Errors', $errors],
            ]
        );

        return Command::SUCCESS;
    }
}
