<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ISP Integration Services
    |--------------------------------------------------------------------------
    */

    'mikrotik' => [
        'enabled' => env('MIKROTIK_ENABLED', false),
        'host' => env('MIKROTIK_HOST', ''),
        'port' => (int) env('MIKROTIK_PORT', 8728),
        'username' => env('MIKROTIK_USERNAME', ''),
        'password' => env('MIKROTIK_PASSWORD', ''),
        'use_ssl' => env('MIKROTIK_USE_SSL', false),
    ],

    'genieacs' => [
        'url' => env('GENIEACS_URL', 'http://localhost:7557'),
        'username' => env('GENIEACS_USERNAME', ''),
        'password' => env('GENIEACS_PASSWORD', ''),
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'http://localhost:3000'),
        'api_key' => env('WHATSAPP_API_KEY', ''),
        'sender' => env('WHATSAPP_SENDER', ''),
        'admin_phone' => env('WHATSAPP_ADMIN_PHONE', ''),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
        'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY', ''),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN', ''),
    ],

    'payment' => [
        'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'midtrans'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RADIUS Server Integration
    |--------------------------------------------------------------------------
    */
    'radius' => [
        'enabled' => env('RADIUS_ENABLED', false),
        'host' => env('RADIUS_DB_HOST', '127.0.0.1'),
        'port' => env('RADIUS_DB_PORT', 3306),
        'database' => env('RADIUS_DB_DATABASE', 'radius'),
        'username' => env('RADIUS_DB_USERNAME', 'radius'),
        'password' => env('RADIUS_DB_PASSWORD', ''),
        'nas_secret' => env('RADIUS_NAS_SECRET', 'testing123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SNMP Network Monitoring
    |--------------------------------------------------------------------------
    */
    'snmp' => [
        'enabled' => env('SNMP_ENABLED', false),
        'community' => env('SNMP_COMMUNITY', 'public'),
        'version' => env('SNMP_VERSION', '2c'),
        'timeout' => env('SNMP_TIMEOUT', 5),
        'retries' => env('SNMP_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | CRM Integration
    |--------------------------------------------------------------------------
    */
    'crm' => [
        'enabled' => env('CRM_ENABLED', false),
        'provider' => env('CRM_PROVIDER', 'hubspot'), // hubspot, salesforce, zoho
        'api_key' => env('CRM_API_KEY', ''),
        'api_url' => env('CRM_API_URL', ''),
        'webhook_secret' => env('CRM_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Accounting Integration
    |--------------------------------------------------------------------------
    */
    'accounting' => [
        'enabled' => env('ACCOUNTING_ENABLED', false),
        'provider' => env('ACCOUNTING_PROVIDER', 'accurate'), // accurate, jurnal, zahir
        'api_key' => env('ACCOUNTING_API_KEY', ''),
        'api_url' => env('ACCOUNTING_API_URL', ''),
        'company_id' => env('ACCOUNTING_COMPANY_ID', ''),
    ],

];
