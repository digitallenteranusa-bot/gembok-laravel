<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PaymentGatewayService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected $paymentGateway;
    protected $whatsapp;

    public function __construct(PaymentGatewayService $paymentGateway, WhatsAppService $whatsapp)
    {
        $this->paymentGateway = $paymentGateway;
        $this->whatsapp = $whatsapp;
    }

    /**
     * Handle Midtrans notification webhook
     */
    public function midtrans(Request $request)
    {
        Log::info('Midtrans webhook received', $request->all());

        try {
            // Verify signature
            $serverKey = config('services.midtrans.server_key');
            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $signature = $request->signature_key;

            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signature !== $expectedSignature) {
                Log::warning('Midtrans invalid signature', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            // Process notification
            $result = $this->paymentGateway->handleMidtransNotification($request->all());

            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 400);
            }

            // Update invoice
            $invoice = Invoice::find($result['invoice_id']);
            
            if (!$invoice) {
                Log::error('Invoice not found', ['invoice_id' => $result['invoice_id']]);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            if ($result['status'] === 'paid') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_date' => now(),
                    'payment_method' => $result['payment_type'] ?? 'midtrans',
                    'transaction_id' => $result['transaction_id'],
                ]);

                // Send WhatsApp confirmation
                $customer = $invoice->customer;
                if ($customer && $customer->phone) {
                    $this->whatsapp->sendPaymentConfirmation($customer, $invoice);
                }

                Log::info('Invoice paid via Midtrans', ['invoice_id' => $invoice->id]);
            } elseif ($result['status'] === 'failed') {
                $invoice->update(['status' => 'failed']);
                Log::info('Invoice payment failed', ['invoice_id' => $invoice->id]);
            }

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Midtrans webhook error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Handle Xendit webhook
     */
    public function xendit(Request $request)
    {
        Log::info('Xendit webhook received', $request->all());

        try {
            // Verify callback token
            $callbackToken = $request->header('x-callback-token');
            $expectedToken = config('services.xendit.callback_token');

            if ($callbackToken !== $expectedToken) {
                Log::warning('Xendit invalid callback token');
                return response()->json(['message' => 'Invalid callback token'], 403);
            }

            // Process webhook
            $result = $this->paymentGateway->handleXenditWebhook($request->all());

            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 400);
            }

            // Update invoice
            $invoice = Invoice::find($result['invoice_id']);
            
            if (!$invoice) {
                Log::error('Invoice not found', ['invoice_id' => $result['invoice_id']]);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            if ($result['status'] === 'paid') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_date' => now(),
                    'payment_method' => $result['payment_method'] ?? 'xendit',
                    'transaction_id' => $result['transaction_id'],
                ]);

                // Send WhatsApp confirmation
                $customer = $invoice->customer;
                if ($customer && $customer->phone) {
                    $this->whatsapp->sendPaymentConfirmation($customer, $invoice);
                }

                Log::info('Invoice paid via Xendit', ['invoice_id' => $invoice->id]);
            } elseif ($result['status'] === 'failed') {
                $invoice->update(['status' => 'failed']);
                Log::info('Invoice payment failed', ['invoice_id' => $invoice->id]);
            }

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Xendit webhook error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }
}
