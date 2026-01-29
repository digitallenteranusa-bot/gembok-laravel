<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Services\MikrotikServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMikrotikUsers extends Command
{
    protected $signature = 'mikrotik:sync-users
        {--create : Create missing PPPoE secrets}
        {--update : Update existing secrets}
        {--router= : Router ID to sync (use "all" for all routers)}';

    protected $description = 'Sync customers with Mikrotik PPPoE secrets';

    public function handle()
    {
        $routerOption = $this->option('router');

        // Determine which routers to sync
        if ($routerOption === 'all') {
            $routers = MikrotikRouter::enabled()->get();
            if ($routers->isEmpty()) {
                $this->warn('No enabled routers found. Using default configuration.');
                return $this->syncForRouter(null);
            }

            $totalCreated = 0;
            $totalUpdated = 0;
            $totalErrors = 0;

            foreach ($routers as $router) {
                $this->info("\n=== Syncing Router: {$router->name} ({$router->host}) ===");
                $result = $this->syncForRouter($router);
                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalErrors += $result['errors'];
            }

            $this->newLine();
            $this->info("=== Total Results ===");
            $this->table(
                ['Action', 'Count'],
                [
                    ['Created', $totalCreated],
                    ['Updated', $totalUpdated],
                    ['Errors', $totalErrors],
                ]
            );

            return Command::SUCCESS;
        }

        // Single router or default
        $router = null;
        if ($routerOption) {
            $router = MikrotikRouter::find($routerOption);
            if (!$router) {
                $this->error("Router with ID {$routerOption} not found!");
                return Command::FAILURE;
            }
            $this->info("Syncing Router: {$router->name} ({$router->host})");
        }

        $result = $this->syncForRouter($router);

        $this->newLine(2);
        $this->info("Sync completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Errors', $result['errors']],
            ]
        );

        return Command::SUCCESS;
    }

    protected function syncForRouter(?MikrotikRouter $router): array
    {
        $mikrotik = $router
            ? MikrotikServiceFactory::forRouter($router)
            : MikrotikServiceFactory::default();

        if (!$mikrotik->isConnected()) {
            $this->error('Failed to connect to Mikrotik!');
            return ['created' => 0, 'updated' => 0, 'errors' => 0];
        }

        $this->info('Connected to Mikrotik successfully.');

        // Build customer query
        $query = Customer::where('status', 'active')
            ->whereNotNull('pppoe_username')
            ->with('package');

        // Filter by router if specified
        if ($router) {
            $query->where(function ($q) use ($router) {
                $q->where('mikrotik_router_id', $router->id)
                  ->orWhereNull('mikrotik_router_id');
            });
        }

        $customers = $query->get();

        $this->info("Found {$customers->count()} active customers with PPPoE credentials.");

        $created = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            try {
                $data = [
                    'username' => $customer->pppoe_username,
                    'password' => $customer->pppoe_password,
                    'profile' => $customer->package->mikrotik_profile ?? 'default',
                    'comment' => "Customer: {$customer->name} (ID: {$customer->id})",
                ];

                if ($this->option('create')) {
                    $result = $mikrotik->createPPPoESecret($data);
                    if ($result) {
                        $created++;
                    }
                }

                if ($this->option('update')) {
                    $result = $mikrotik->updatePPPoESecret($customer->pppoe_username, $data);
                    if ($result) {
                        $updated++;
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                Log::error('Mikrotik sync error', [
                    'customer_id' => $customer->id,
                    'router_id' => $router?->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
