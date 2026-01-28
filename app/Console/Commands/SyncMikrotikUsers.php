<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\MikrotikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMikrotikUsers extends Command
{
    protected $signature = 'mikrotik:sync-users {--create : Create missing PPPoE secrets} {--update : Update existing secrets}';
    protected $description = 'Sync customers with Mikrotik PPPoE secrets';

    protected $mikrotik;

    public function __construct(MikrotikService $mikrotik)
    {
        parent::__construct();
        $this->mikrotik = $mikrotik;
    }

    public function handle()
    {
        if (!$this->mikrotik->isConnected()) {
            $this->error('Failed to connect to Mikrotik!');
            return Command::FAILURE;
        }

        $this->info('Connected to Mikrotik successfully.');

        $customers = Customer::where('status', 'active')
            ->whereNotNull('pppoe_username')
            ->with('package')
            ->get();

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
                    $result = $this->mikrotik->createPPPoESecret($data);
                    if ($result) {
                        $created++;
                    }
                }

                if ($this->option('update')) {
                    $result = $this->mikrotik->updatePPPoESecret($customer->pppoe_username, $data);
                    if ($result) {
                        $updated++;
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                Log::error('Mikrotik sync error', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Errors', $errors],
            ]
        );

        return Command::SUCCESS;
    }
}
