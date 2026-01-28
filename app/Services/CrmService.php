<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmService
{
    protected $enabled;
    protected $provider;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->enabled = config('services.crm.enabled', false);
        $this->provider = config('services.crm.provider', 'hubspot');
        $this->apiKey = config('services.crm.api_key', '');
        $this->apiUrl = config('services.crm.api_url', '');
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
     * Sync customer to CRM
     */
    public function syncCustomer($customer): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'CRM not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'hubspot':
                    return $this->syncToHubspot($customer);
                case 'salesforce':
                    return $this->syncToSalesforce($customer);
                case 'zoho':
                    return $this->syncToZoho($customer);
                default:
                    return ['success' => false, 'message' => 'Unknown CRM provider'];
            }
        } catch (\Exception $e) {
            Log::error('CRM sync failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create deal/opportunity in CRM
     */
    public function createDeal($customer, $package, $amount): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'CRM not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'hubspot':
                    return $this->createHubspotDeal($customer, $package, $amount);
                case 'salesforce':
                    return $this->createSalesforceDeal($customer, $package, $amount);
                case 'zoho':
                    return $this->createZohoDeal($customer, $package, $amount);
                default:
                    return ['success' => false, 'message' => 'Unknown CRM provider'];
            }
        } catch (\Exception $e) {
            Log::error('CRM create deal failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Log activity in CRM
     */
    public function logActivity($customerId, $type, $description): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'CRM not enabled'];
        }

        try {
            switch ($this->provider) {
                case 'hubspot':
                    return $this->logHubspotActivity($customerId, $type, $description);
                default:
                    return ['success' => true, 'message' => 'Activity logging not implemented'];
            }
        } catch (\Exception $e) {
            Log::error('CRM log activity failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // HubSpot Implementation
    protected function syncToHubspot($customer): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.hubapi.com/crm/v3/objects/contacts', [
            'properties' => [
                'email' => $customer->email,
                'firstname' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'customer_id' => $customer->customer_id,
            ],
        ]);

        if ($response->successful()) {
            Log::info('Customer synced to HubSpot', ['customer_id' => $customer->id]);
            return ['success' => true, 'crm_id' => $response->json('id')];
        }

        return ['success' => false, 'message' => $response->body()];
    }

    protected function createHubspotDeal($customer, $package, $amount): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.hubapi.com/crm/v3/objects/deals', [
            'properties' => [
                'dealname' => "Subscription - {$customer->name} - {$package->name}",
                'amount' => $amount,
                'pipeline' => 'default',
                'dealstage' => 'closedwon',
            ],
        ]);

        return $response->successful() 
            ? ['success' => true, 'deal_id' => $response->json('id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function logHubspotActivity($customerId, $type, $description): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.hubapi.com/crm/v3/objects/notes', [
            'properties' => [
                'hs_note_body' => "[{$type}] {$description}",
                'hs_timestamp' => now()->timestamp * 1000,
            ],
        ]);

        return ['success' => $response->successful()];
    }

    // Salesforce Implementation
    protected function syncToSalesforce($customer): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/services/data/v52.0/sobjects/Contact', [
            'FirstName' => $customer->name,
            'Email' => $customer->email,
            'Phone' => $customer->phone,
            'MailingStreet' => $customer->address,
        ]);

        return $response->successful()
            ? ['success' => true, 'crm_id' => $response->json('id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function createSalesforceDeal($customer, $package, $amount): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/services/data/v52.0/sobjects/Opportunity', [
            'Name' => "Subscription - {$customer->name}",
            'Amount' => $amount,
            'StageName' => 'Closed Won',
            'CloseDate' => now()->format('Y-m-d'),
        ]);

        return $response->successful()
            ? ['success' => true, 'deal_id' => $response->json('id')]
            : ['success' => false, 'message' => $response->body()];
    }

    // Zoho Implementation
    protected function syncToZoho($customer): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://www.zohoapis.com/crm/v2/Contacts', [
            'data' => [[
                'First_Name' => $customer->name,
                'Email' => $customer->email,
                'Phone' => $customer->phone,
                'Mailing_Street' => $customer->address,
            ]],
        ]);

        return $response->successful()
            ? ['success' => true, 'crm_id' => $response->json('data.0.details.id')]
            : ['success' => false, 'message' => $response->body()];
    }

    protected function createZohoDeal($customer, $package, $amount): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://www.zohoapis.com/crm/v2/Deals', [
            'data' => [[
                'Deal_Name' => "Subscription - {$customer->name}",
                'Amount' => $amount,
                'Stage' => 'Closed Won',
            ]],
        ]);

        return $response->successful()
            ? ['success' => true, 'deal_id' => $response->json('data.0.details.id')]
            : ['success' => false, 'message' => $response->body()];
    }
}
