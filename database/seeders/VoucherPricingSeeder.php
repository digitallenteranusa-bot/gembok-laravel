<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherPricing;

class VoucherPricingSeeder extends Seeder
{
    public function run(): void
    {
        $pricings = [
            [
                'package_name' => 'Voucher 1 Jam',
                'customer_price' => 3000,
                'agent_price' => 2500,
                'commission_amount' => 500,
                'duration' => 1,
                'description' => 'Voucher internet 1 jam',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 3 Jam',
                'customer_price' => 5000,
                'agent_price' => 4000,
                'commission_amount' => 1000,
                'duration' => 3,
                'description' => 'Voucher internet 3 jam',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 6 Jam',
                'customer_price' => 8000,
                'agent_price' => 6500,
                'commission_amount' => 1500,
                'duration' => 6,
                'description' => 'Voucher internet 6 jam',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 12 Jam',
                'customer_price' => 12000,
                'agent_price' => 10000,
                'commission_amount' => 2000,
                'duration' => 12,
                'description' => 'Voucher internet 12 jam',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 24 Jam',
                'customer_price' => 20000,
                'agent_price' => 17000,
                'commission_amount' => 3000,
                'duration' => 24,
                'description' => 'Voucher internet 24 jam (1 hari)',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 3 Hari',
                'customer_price' => 50000,
                'agent_price' => 42000,
                'commission_amount' => 8000,
                'duration' => 72,
                'description' => 'Voucher internet 3 hari',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 7 Hari',
                'customer_price' => 100000,
                'agent_price' => 85000,
                'commission_amount' => 15000,
                'duration' => 168,
                'description' => 'Voucher internet 7 hari (1 minggu)',
                'is_active' => true,
            ],
            [
                'package_name' => 'Voucher 30 Hari',
                'customer_price' => 350000,
                'agent_price' => 300000,
                'commission_amount' => 50000,
                'duration' => 720,
                'description' => 'Voucher internet 30 hari (1 bulan)',
                'is_active' => true,
            ],
        ];

        foreach ($pricings as $pricing) {
            VoucherPricing::updateOrCreate(
                ['package_name' => $pricing['package_name']],
                $pricing
            );
        }
    }
}
