<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Paket Internet Dasar',
                'speed' => '10 Mbps',
                'price' => 100000,
                'tax_rate' => 11.0,
                'description' => 'Paket internet dasar 10 Mbps unlimited',
                'is_active' => true,
                'pppoe_profile' => 'default',
            ],
            [
                'name' => 'Paket Internet Standard',
                'speed' => '20 Mbps',
                'price' => 150000,
                'tax_rate' => 11.0,
                'description' => 'Paket internet standard 20 Mbps unlimited',
                'is_active' => true,
                'pppoe_profile' => 'standard',
            ],
            [
                'name' => 'Paket Internet Premium',
                'speed' => '50 Mbps',
                'price' => 250000,
                'tax_rate' => 11.0,
                'description' => 'Paket internet premium 50 Mbps unlimited',
                'is_active' => true,
                'pppoe_profile' => 'premium',
            ],
        ];

        foreach ($packages as $package) {
            \App\Models\Package::create($package);
        }
    }
}
