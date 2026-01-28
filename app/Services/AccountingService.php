<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    protected $enabled;
    protected $provider;
    protected $apiKey;
    protected $apiUrl;
    protected $companyId;

    public function __construct()
    {
        $this->enabled = config('services.accounting.enabled', false);
        $this->provider = config('services.accounting.provider', 'accurate');
        $this->apiKey = config('services.accounting.api_key', '');
        $this->apiUrl = config('services.accounting.api_url', '');
        $this->companyId = config('services.accounting.company_id', '');
    }

    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Create invoice in accounting system
     */
    public function createInvoice($invoice): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Accounting not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'accurate':
                    return $this->createAccurateInvoice($invoice);
                case 'jurnal':
                    return $this->createJurnalInvoice($invoice);
                case 'zahir':
                    return $this->createZahirInvoice($invoice);
                default:
                    return ['success' => false, 'message' => 'Unknown provider'];
            }
        } catch (\Exception $e) {
            Log::error('Accounting createInvoice failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Record payment in accounting system
     */
    public function recordPayment($payment): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Accounting not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'accurate':
                    return $this->recordAccuratePayment($payment);
                case 'jurnal':
                    return $this->recordJurnalPayment($payment);
                case 'zahir':
                    return $this->recordZahirPayment($payment);
                default:
                    return ['success' => false, 'message' => 'Unknown provider'];
            }
        } catch (\Exception $e) {
            Log::error('Accounting recordPayment failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync customer to accounting system
     */
    public function syncCustomer($customer): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Accounting not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'accurate':
                    return $this->syncAccurateCustomer($customer);
                case 'jurnal':
                    return $this->syncJurnalCustomer($customer);
                default:
                    return ['success' => true, 'message' => 'Customer sync not implemented'];
            }
        } catch (\Exception $e) {
            Log::error('Accounting syncCustomer failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Accurate Online Implementation
    protected function createAccurateInvoice($invoice): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Session-ID' => $this->companyId,
        ])->post($this->apiUrl . '/accurate/api/sales-invoice/save.do', [
            'customerNo' => $invoice->customer->customer_id,
            'transDate' => $invoice->created_at->format('d/m/Y'),
            'dueDate' => $invoice->due_date->format('d/m/Y'),
            'number' => $invoice->invoice_number,
            'detailItem' => [[
                'itemNo' => 'INTERNET',
                'detailName' => $invoice->description ?? 'Internet Service',
                'unitPrice' => $invoice->amount,
                'quantity' => 1,
            ]],
        ]);

        if ($response->successful() && $response->json('s')) {
            Log::info('Invoice synced to Accurate', ['invoice_id' => $invoice->id]);
            return ['success' => true, 'ref_id' => $response->json('r.id')];
        }

        return ['success' => false, 'message' => $response->json('d') ?? $response->body()];
    }

    protected function recordAccuratePayment($payment): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Session-ID' => $this->companyId,
        ])->post($this->apiUrl . '/accurate/api/customer-receipt/save.do', [
            'customerNo' => $payment->invoice->customer->customer_id,
            'transDate' => $payment->paid_at->format('d/m/Y'),
            'bankNo' => 'BANK',
            'detailInvoice' => [[
                'invoiceNo' => $payment->invoice->invoice_number,
                'paymentAmount' => $payment->amount,
            ]],
        ]);

        return $response->successful() && $response->json('s')
            ? ['success' => true, 'ref_id' => $response->json('r.id')]
            : ['success' => false, 'message' => $response->json('d') ?? $response->body()];
    }

    protected function syncAccurateCustomer($customer): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Session-ID' => $this->companyId,
        ])->post($this->apiUrl . '/accurate/api/customer/save.do', [
            'customerNo' => $customer->customer_id,
            'name' => $customer->name,
            'mobilePhone' => $customer->phone,
            'email' => $customer->email,
            'billStreet' => $customer->address,
        ]);

        return $response->successful() && $response->json('s')
            ? ['success' => true, 'ref_id' => $response->json('r.id')]
            : ['success' => false, 'message' => $response->json('d') ?? $response->body()];
    }

    // Jurnal.id Implementation
    protected function createJurnalInvoice($invoice): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/core/api/v1/sales_invoices', [
            'sales_invoice' => [
                'person_id' => $invoice->customer->customer_id,
                'transaction_date' => $invoice->created_at->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'transaction_no' => $invoice->invoice_number,
                'transaction_lines_attributes' => [[
                    'product_name' => $invoice->description ?? 'Internet Service',
                    'quantity' => 1,
                    'rate' => $invoice->amount,
                ]],
            ],
        ]);

        return $response->successful()
            ? ['success' => true, 'ref_id' => $response->json('sales_invoice.id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function recordJurnalPayment($payment): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/core/api/v1/receive_payments', [
            'receive_payment' => [
                'person_id' => $payment->invoice->customer->customer_id,
                'payment_date' => $payment->paid_at->format('Y-m-d'),
                'payment_method_name' => $payment->method ?? 'Cash',
                'receive_payment_lines_attributes' => [[
                    'transaction_no' => $payment->invoice->invoice_number,
                    'amount' => $payment->amount,
                ]],
            ],
        ]);

        return $response->successful()
            ? ['success' => true, 'ref_id' => $response->json('receive_payment.id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function syncJurnalCustomer($customer): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/core/api/v1/contacts', [
            'contact' => [
                'display_name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
            ],
        ]);

        return $response->successful()
            ? ['success' => true, 'ref_id' => $response->json('contact.id')]
            : ['success' => false, 'message' => $response->body()];
    }

    // Zahir Implementation (basic)
    protected function createZahirInvoice($invoice): array
    {
        // Zahir uses different API structure
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl . '/api/sales-invoice', [
            'customer_code' => $invoice->customer->customer_id,
            'invoice_date' => $invoice->created_at->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'invoice_no' => $invoice->invoice_number,
            'items' => [[
                'description' => $invoice->description ?? 'Internet Service',
                'amount' => $invoice->amount,
            ]],
        ]);

        return $response->successful()
            ? ['success' => true, 'ref_id' => $response->json('id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function recordZahirPayment($payment): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl . '/api/payment-receipt', [
            'customer_code' => $payment->invoice->customer->customer_id,
            'payment_date' => $payment->paid_at->format('Y-m-d'),
            'invoice_no' => $payment->invoice->invoice_number,
            'amount' => $payment->amount,
        ]);

        return $response->successful()
            ? ['success' => true, 'ref_id' => $response->json('id')]
            : ['success' => false, 'message' => $response->body()];
    }
}
