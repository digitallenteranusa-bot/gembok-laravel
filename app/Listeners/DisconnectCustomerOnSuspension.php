<?php

namespace App\Listeners;

use App\Events\CustomerSuspended;
use App\Services\MikrotikServiceFactory;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DisconnectCustomerOnSuspension implements ShouldQueue
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function handle(CustomerSuspended $event): void
    {
        $customer = $event->customer;

        try {
            // Disconnect from Mikrotik using customer's router
            if ($customer->pppoe_username) {
                $mikrotik = MikrotikServiceFactory::forCustomer($customer);
                if ($mikrotik->isConnected()) {
                    $mikrotik->disconnectPPPoE($customer->pppoe_username);
                    $mikrotik->deletePPPoESecret($customer->pppoe_username);
                }
            }

            // Send suspension notice via WhatsApp
            if ($customer->phone) {
                $this->whatsapp->sendSuspensionNotice($customer);
            }

            Log::info('Customer disconnected on suspension', ['customer_id' => $customer->id]);

        } catch (\Exception $e) {
            Log::error('Failed to disconnect customer on suspension', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
