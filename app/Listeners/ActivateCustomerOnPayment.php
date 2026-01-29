<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Services\MikrotikServiceFactory;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ActivateCustomerOnPayment implements ShouldQueue
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $customer = $invoice->customer;

        if (!$customer) {
            return;
        }

        try {
            // Reactivate customer if suspended
            if ($customer->status === 'suspended') {
                $customer->update(['status' => 'active']);

                // Create/Update PPPoE secret in Mikrotik using customer's router
                if ($customer->pppoe_username) {
                    $mikrotik = MikrotikServiceFactory::forCustomer($customer);
                    if ($mikrotik->isConnected()) {
                        $mikrotik->createPPPoESecret([
                            'username' => $customer->pppoe_username,
                            'password' => $customer->pppoe_password,
                            'profile' => $customer->package->mikrotik_profile ?? 'default',
                            'comment' => "Customer: {$customer->name}",
                        ]);
                    }
                }

                Log::info('Customer reactivated after payment', ['customer_id' => $customer->id]);
            }

            // Send payment confirmation via WhatsApp
            if ($customer->phone) {
                $this->whatsapp->sendPaymentConfirmation($customer, $invoice);
            }

        } catch (\Exception $e) {
            Log::error('Failed to activate customer on payment', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
