<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Collector;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $collectors = Collector::all();
        $paidInvoices = Invoice::where('status', 'paid')->get();

        foreach ($paidInvoices as $invoice) {
            // Skip if invoice has no total amount
            $amount = $invoice->total ?? $invoice->amount ?? 0;
            if ($amount <= 0) {
                continue;
            }

            $collector = $collectors->isNotEmpty() ? $collectors->random() : null;
            $commissionRate = $collector ? ($collector->commission_rate ?? 2) : 0;
            $commission = ($amount * $commissionRate) / 100;

            Payment::create([
                'invoice_id' => $invoice->id,
                'collector_id' => $collector && rand(0, 1) ? $collector->id : null,
                'amount' => $amount,
                'payment_method' => $invoice->payment_method ?? ['cash', 'transfer', 'midtrans', 'xendit'][rand(0, 3)],
                'commission' => $commission,
                'notes' => rand(0, 1) ? 'Pembayaran tepat waktu' : null,
                'reference_number' => 'PAY-' . strtoupper(uniqid()),
                'paid_at' => $invoice->paid_at ?? Carbon::now()->subDays(rand(1, 30)),
                'created_at' => $invoice->paid_at ?? Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
