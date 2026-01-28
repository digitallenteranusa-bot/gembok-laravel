<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'ALIJAYA DIGITAL NETWORK'],
            ['key' => 'company_phone', 'value' => '081947215703'],
            ['key' => 'company_email', 'value' => 'info@alijaya.com'],
            ['key' => 'company_address', 'value' => 'Jl. Contoh Alamat No. 123'],
            ['key' => 'default_commission_rate', 'value' => '10'],
            ['key' => 'tax_rate', 'value' => '11'],
            ['key' => 'currency', 'value' => 'IDR'],
            ['key' => 'timezone', 'value' => 'Asia/Jakarta'],
        ];

        foreach ($settings as $setting) {
            \App\Models\AppSetting::create($setting);
        }
    }
}
