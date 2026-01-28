<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class IntegrationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Mikrotik Settings
            ['key' => 'mikrotik_host', 'value' => '192.168.88.1', 'group' => 'mikrotik'],
            ['key' => 'mikrotik_port', 'value' => '8728', 'group' => 'mikrotik'],
            ['key' => 'mikrotik_username', 'value' => 'admin', 'group' => 'mikrotik'],
            ['key' => 'mikrotik_password', 'value' => '', 'group' => 'mikrotik'],
            ['key' => 'mikrotik_enabled', 'value' => 'false', 'group' => 'mikrotik'],
            
            // GenieACS Settings
            ['key' => 'genieacs_url', 'value' => 'http://localhost:7557', 'group' => 'genieacs'],
            ['key' => 'genieacs_username', 'value' => 'admin', 'group' => 'genieacs'],
            ['key' => 'genieacs_password', 'value' => '', 'group' => 'genieacs'],
            ['key' => 'genieacs_enabled', 'value' => 'false', 'group' => 'genieacs'],
            
            // WhatsApp Gateway Settings
            ['key' => 'whatsapp_api_url', 'value' => 'http://localhost:3000', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_api_key', 'value' => '', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_sender', 'value' => '6281234567890', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_enabled', 'value' => 'false', 'group' => 'whatsapp'],
            
            // Midtrans Settings
            ['key' => 'midtrans_server_key', 'value' => '', 'group' => 'midtrans'],
            ['key' => 'midtrans_client_key', 'value' => '', 'group' => 'midtrans'],
            ['key' => 'midtrans_is_production', 'value' => 'false', 'group' => 'midtrans'],
            ['key' => 'midtrans_enabled', 'value' => 'false', 'group' => 'midtrans'],
            
            // Xendit Settings
            ['key' => 'xendit_secret_key', 'value' => '', 'group' => 'xendit'],
            ['key' => 'xendit_public_key', 'value' => '', 'group' => 'xendit'],
            ['key' => 'xendit_callback_token', 'value' => '', 'group' => 'xendit'],
            ['key' => 'xendit_enabled', 'value' => 'false', 'group' => 'xendit'],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }
    }
}
