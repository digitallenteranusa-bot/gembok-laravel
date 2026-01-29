<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use Illuminate\Support\Facades\Log;

class MikrotikServiceFactory
{
    /**
     * Create a MikrotikService instance for a specific router
     */
    public static function forRouter(MikrotikRouter $router): MikrotikService
    {
        return new MikrotikService($router->toConnectionConfig(), $router);
    }

    /**
     * Create a MikrotikService instance for a customer's router
     */
    public static function forCustomer(Customer $customer): MikrotikService
    {
        $router = $customer->getRouter();

        if ($router) {
            return static::forRouter($router);
        }

        // Fallback to default configuration
        return new MikrotikService();
    }

    /**
     * Create a MikrotikService instance by router ID
     */
    public static function forRouterId(?int $routerId): MikrotikService
    {
        if ($routerId) {
            $router = MikrotikRouter::find($routerId);
            if ($router && $router->enabled) {
                return static::forRouter($router);
            }
        }

        // Fallback to default router
        return static::default();
    }

    /**
     * Create a MikrotikService instance for the default router
     */
    public static function default(): MikrotikService
    {
        $router = MikrotikRouter::getDefault();

        if ($router) {
            return static::forRouter($router);
        }

        // Fallback to IntegrationSetting or config
        return new MikrotikService();
    }

    /**
     * Test connection to a router
     */
    public static function testConnection(MikrotikRouter $router): array
    {
        try {
            $service = static::forRouter($router);
            $connected = $service->isConnected();

            if ($connected) {
                $identity = $service->getSystemIdentity();
                $router->updateConnectionStatus(true, 'Connected successfully', $identity);

                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'identity' => $identity,
                ];
            }

            $router->updateConnectionStatus(false, 'Failed to connect');

            return [
                'success' => false,
                'message' => 'Failed to connect to router',
            ];
        } catch (\Exception $e) {
            Log::error('Mikrotik connection test failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            $router->updateConnectionStatus(false, $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all available routers with their services
     * Returns array of ['router' => MikrotikRouter, 'service' => MikrotikService]
     */
    public static function getAllEnabled(): array
    {
        $routers = MikrotikRouter::getEnabled();
        $result = [];

        foreach ($routers as $router) {
            $result[] = [
                'router' => $router,
                'service' => static::forRouter($router),
            ];
        }

        return $result;
    }
}
