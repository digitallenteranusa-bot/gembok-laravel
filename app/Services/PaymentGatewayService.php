<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    protected $gateway;
    protected $midtransServerKey;
    protected $midtransClientKey;
    protected $midtransUrl;
    protected $xenditSecretKey;
    protected $xenditUrl;

    public function __construct()
    {
        $this->gateway = config('services.payment.default_gateway', 'midtrans');
        
        // Midtrans config
        $this->midtransServerKey = config('services.midtrans.server_key');
        $this->midtransClientKey = config('services.midtrans.client_key');
        $this->midtransUrl = config('services.midtrans.is_production') 
            ? 'https://api.midtrans.com' 
            : 'https://api.sandbox.midtrans.com';
        
        // Xendit config
        $this->xenditSecretKey = config('services.xendit.secret_key');
        $this->xenditUrl = 'https://api.xendit.co';
    }

    /**
     * Create transaction for order (used by OrderController)
     */
    public function createTransaction(array $data)
    {
        try {
            $params = [
                'transaction_details' => [
                    'order_id' => $data['order_id'],
                    'gross_amount' => (int) $data['gross_amount'],
                ],
                'customer_details' => [
                    'first_name' => $data['customer_name'],
                    'email' => $data['customer_email'] ?? 'customer@example.com',
                    'phone' => $data['customer_phone'],
                ],
                'item_details' => $data['item_details'] ?? [],
            ];

            $snapUrl = config('services.midtrans.is_production')
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $response = Http::withBasicAuth($this->midtransServerKey, '')
                ->post($snapUrl, $params);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'token' => $responseData['token'],
                    'redirect_url' => $responseData['redirect_url'],
                    'order_id' => $data['order_id']
                ];
            }

            Log::error('Midtrans transaction failed', ['response' => $response->body()]);
            return ['success' => false, 'message' => 'Transaction creation failed'];
            
        } catch (\Exception $e) {
            Log::error('Midtrans transaction exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create payment for invoice
     */
    public function createPayment($invoice, $customer, $gateway = null)
    {
        $gateway = $gateway ?? $this->gateway;

        return match($gateway) {
            'midtrans' => $this->createMidtransPayment($invoice, $customer),
            'xendit' => $this->createXenditPayment($invoice, $customer),
            default => throw new \Exception('Invalid payment gateway')
        };
    }

    /**
     * Create Midtrans payment
     */
    protected function createMidtransPayment($invoice, $customer)
    {
        try {
            $orderId = 'INV-' . $invoice->id . '-' . time();
            
            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $invoice->amount,
                ],
                'customer_details' => [
                    'first_name' => $customer->name,
                    'email' => $customer->email ?? 'customer@example.com',
                    'phone' => $customer->phone,
                ],
                'item_details' => [
                    [
                        'id' => $invoice->invoice_number,
                        'price' => (int) $invoice->amount,
                        'quantity' => 1,
                        'name' => $invoice->package->name ?? 'Internet Service',
                    ]
                ],
                'callbacks' => [
                    'finish' => route('payment.finish'),
                ]
            ];

            $response = Http::withBasicAuth($this->midtransServerKey, '')
                ->post($this->midtransUrl . '/v2/charge', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'gateway' => 'midtrans',
                    'order_id' => $orderId,
                    'payment_url' => $data['redirect_url'] ?? null,
                    'token' => $data['token'] ?? null,
                    'data' => $data
                ];
            }

            Log::error('Midtrans payment failed', ['response' => $response->body()]);
            return ['success' => false, 'message' => 'Payment creation failed'];
            
        } catch (\Exception $e) {
            Log::error('Midtrans exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Midtrans Snap Token
     */
    public function createSnapToken($invoice, $customer)
    {
        try {
            $orderId = 'INV-' . $invoice->id . '-' . time();
            
            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $invoice->amount,
                ],
                'customer_details' => [
                    'first_name' => $customer->name,
                    'email' => $customer->email ?? 'customer@example.com',
                    'phone' => $customer->phone,
                ],
                'item_details' => [
                    [
                        'id' => $invoice->invoice_number,
                        'price' => (int) $invoice->amount,
                        'quantity' => 1,
                        'name' => $invoice->package->name ?? 'Internet Service',
                    ]
                ],
            ];

            $snapUrl = config('services.midtrans.is_production')
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $response = Http::withBasicAuth($this->midtransServerKey, '')
                ->post($snapUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'token' => $data['token'],
                    'redirect_url' => $data['redirect_url'],
                    'order_id' => $orderId
                ];
            }

            return ['success' => false, 'message' => 'Failed to create snap token'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Xendit Invoice
     */
    protected function createXenditPayment($invoice, $customer)
    {
        try {
            $externalId = 'INV-' . $invoice->id . '-' . time();
            
            $params = [
                'external_id' => $externalId,
                'amount' => (int) $invoice->amount,
                'payer_email' => $customer->email ?? 'customer@example.com',
                'description' => 'Payment for ' . $invoice->invoice_number,
                'invoice_duration' => 86400, // 24 hours
                'customer' => [
                    'given_names' => $customer->name,
                    'mobile_number' => $this->formatPhone($customer->phone),
                    'email' => $customer->email ?? 'customer@example.com',
                ],
                'success_redirect_url' => route('payment.success'),
                'failure_redirect_url' => route('payment.failed'),
                'items' => [
                    [
                        'name' => $invoice->package->name ?? 'Internet Service',
                        'quantity' => 1,
                        'price' => (int) $invoice->amount,
                    ]
                ]
            ];

            $response = Http::withBasicAuth($this->xenditSecretKey, '')
                ->post($this->xenditUrl . '/v2/invoices', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'gateway' => 'xendit',
                    'order_id' => $externalId,
                    'invoice_id' => $data['id'],
                    'payment_url' => $data['invoice_url'],
                    'data' => $data
                ];
            }

            Log::error('Xendit payment failed', ['response' => $response->body()]);
            return ['success' => false, 'message' => 'Payment creation failed'];
            
        } catch (\Exception $e) {
            Log::error('Xendit exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle Midtrans notification
     */
    public function handleMidtransNotification($payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // Extract invoice ID from order_id (format: INV-{id}-{timestamp})
        preg_match('/INV-(\d+)-/', $orderId, $matches);
        $invoiceId = $matches[1] ?? null;

        if (!$invoiceId) {
            return ['success' => false, 'message' => 'Invalid order ID'];
        }

        $status = 'pending';
        
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            if ($fraudStatus == 'accept' || !$fraudStatus) {
                $status = 'paid';
            }
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $status = 'failed';
        }

        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'status' => $status,
            'transaction_id' => $payload['transaction_id'] ?? null,
            'payment_type' => $payload['payment_type'] ?? null,
        ];
    }

    /**
     * Handle Xendit webhook
     */
    public function handleXenditWebhook($payload)
    {
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;

        // Extract invoice ID from external_id
        preg_match('/INV-(\d+)-/', $externalId, $matches);
        $invoiceId = $matches[1] ?? null;

        if (!$invoiceId) {
            return ['success' => false, 'message' => 'Invalid external ID'];
        }

        $paymentStatus = match($status) {
            'PAID', 'SETTLED' => 'paid',
            'EXPIRED', 'FAILED' => 'failed',
            default => 'pending'
        };

        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'status' => $paymentStatus,
            'transaction_id' => $payload['id'] ?? null,
            'payment_method' => $payload['payment_method'] ?? null,
        ];
    }

    /**
     * Check payment status
     */
    public function checkStatus($orderId, $gateway = null)
    {
        $gateway = $gateway ?? $this->gateway;

        return match($gateway) {
            'midtrans' => $this->checkMidtransStatus($orderId),
            'xendit' => $this->checkXenditStatus($orderId),
            default => null
        };
    }

    protected function checkMidtransStatus($orderId)
    {
        try {
            $response = Http::withBasicAuth($this->midtransServerKey, '')
                ->get($this->midtransUrl . '/v2/' . $orderId . '/status');

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function checkXenditStatus($invoiceId)
    {
        try {
            $response = Http::withBasicAuth($this->xenditSecretKey, '')
                ->get($this->xenditUrl . '/v2/invoices/' . $invoiceId);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '+62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) === '62') {
            $phone = '+' . $phone;
        }
        return $phone;
    }
}
