<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    protected $signature = 'billing:send-reminders {--days=3 : Days before due date}';
    protected $description = 'Send payment reminders for unpaid invoices';

    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $days = $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $this->info("Sending reminders for invoices due on {$targetDate}...");

        $invoices = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', $targetDate)
            ->with(['customer', 'package'])
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found for reminder.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if (!$customer || !$customer->phone) {
                $failed++;
                $bar->advance();
                continue;
            }

            try {
                $result = $this->whatsapp->sendPaymentReminder($customer, $invoice);
                
                if ($result['success']) {
                    $sent++;
                    Log::info('Payment reminder sent', ['invoice_id' => $invoice->id]);
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to send reminder', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Reminder sending completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Sent', $sent],
                ['Failed', $failed],
            ]
        );

        return Command::SUCCESS;
    }
}
