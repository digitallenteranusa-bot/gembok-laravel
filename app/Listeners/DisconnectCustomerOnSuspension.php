<?php

namespace App\Listeners;

use App\Events\CustomerSuspended;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DisconnectCustomerOnSuspension implements ShouldQueue
{
    protected $mikrotik;
    protected $whatsapp;

    public function __construct(MikrotikService $mikrotik, WhatsAppService $whatsapp)
    {
        $this->mikrotik = $mikrotik;
        $this->whatsapp = $whatsapp;
    }

    public function handle(CustomerSuspended $event): void
    {
        $customer = $event->customer;

        try {
            // Disconnect from Mikrotik
            if ($customer->pppoe_username && $this->mikrotik->isConnected()) {
                $this->mikrotik->disconnectPPPoE($customer->pppoe_username);
                $this->mikrotik->deletePPPoESecret($customer->pppoe_username);
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
