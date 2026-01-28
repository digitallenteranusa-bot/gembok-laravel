# 🔌 Integration Guide - GEMBOK LARA

Panduan lengkap untuk integrasi sistem eksternal dengan GEMBOK LARA.

---

## 📡 Mikrotik Integration

### Overview
Integrasi dengan Mikrotik RouterOS untuk manajemen PPPoE dan Hotspot secara otomatis dari dashboard GEMBOK LARA.

### Prerequisites
- Mikrotik RouterOS v6.x atau v7.x
- API Service enabled di Mikrotik
- User dengan permission API & Write

### Configuration

#### 1. Enable API di Mikrotik
```bash
/ip service
set api address=0.0.0.0/0 disabled=no port=8728
```

#### 2. Create API User
```bash
/user add name=api_user password=your_secure_password group=full
```

#### 3. Setup di GEMBOK LARA

Edit `.env`:
```env
MIKROTIK_HOST=192.168.1.1
MIKROTIK_PORT=8728
MIKROTIK_USERNAME=api_user
MIKROTIK_PASSWORD=your_secure_password
MIKROTIK_USE_SSL=false
```

### Features

#### PPPoE Management

**Auto Create PPPoE Secret**
```php
// Ketika customer dibuat/diupdate
$mikrotik->createPPPoESecret([
    'name' => $customer->username,
    'password' => $customer->password,
    'service' => 'pppoe',
    'profile' => $customer->package->pppoe_profile,
    'comment' => "Customer: {$customer->name}"
]);
```

**Update Bandwidth**
```php
// Ketika package diubah
$mikrotik->updatePPPoEProfile([
    'name' => $package->pppoe_profile,
    'rate-limit' => "{$package->speed}M/{$package->speed}M"
]);
```

**Disconnect User**
```php
// Suspend customer
$mikrotik->disconnectPPPoE($customer->username);
```

#### Hotspot Management

**Generate Voucher**
```php
$mikrotik->createHotspotUser([
    'name' => $voucher->code,
    'password' => $voucher->password,
    'profile' => $voucher->profile,
    'limit-uptime' => $voucher->duration,
    'comment' => "Voucher: {$voucher->package}"
]);
```

**Monitor Active Sessions**
```php
$activeSessions = $mikrotik->getActiveHotspotUsers();
```

### API Endpoints

#### Get User Status
```
GET /api/mikrotik/user/{username}/status
```

Response:
```json
{
    "username": "customer001",
    "status": "online",
    "ip_address": "10.10.10.100",
    "uptime": "2h 30m",
    "bytes_in": 1048576,
    "bytes_out": 524288
}
```

#### Control Bandwidth
```
POST /api/mikrotik/user/{username}/bandwidth
```

Request:
```json
{
    "upload": "10M",
    "download": "10M"
}
```

---

## 🌐 GenieACS Integration

### Overview
GenieACS adalah Auto Configuration Server (ACS) open-source untuk manajemen CPE menggunakan protokol TR-069/CWMP.

### Prerequisites
- GenieACS Server v1.2+
- MongoDB v4.x+
- Node.js v14+

### Installation GenieACS

#### 1. Install Dependencies
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y mongodb nodejs npm

# Install GenieACS
sudo npm install -g genieacs
```

#### 2. Configure GenieACS
```bash
# Create config directory
sudo mkdir -p /opt/genieacs/config

# Create config file
sudo nano /opt/genieacs/config/config.json
```

Config:
```json
{
  "MONGODB_CONNECTION_URL": "mongodb://127.0.0.1:27017/genieacs",
  "CWMP_INTERFACE": "0.0.0.0",
  "CWMP_PORT": 7547,
  "CWMP_SSL": false,
  "NBI_INTERFACE": "0.0.0.0",
  "NBI_PORT": 7557,
  "FS_INTERFACE": "0.0.0.0",
  "FS_PORT": 7567,
  "UI_INTERFACE": "0.0.0.0",
  "UI_PORT": 3000
}
```

#### 3. Start Services
```bash
# Start GenieACS services
sudo systemctl start genieacs-cwmp
sudo systemctl start genieacs-nbi
sudo systemctl start genieacs-fs
sudo systemctl start genieacs-ui

# Enable on boot
sudo systemctl enable genieacs-cwmp
sudo systemctl enable genieacs-nbi
sudo systemctl enable genieacs-fs
sudo systemctl enable genieacs-ui
```

### Configuration in GEMBOK LARA

Edit `.env`:
```env
GENIEACS_URL=http://localhost:7557
GENIEACS_USERNAME=admin
GENIEACS_PASSWORD=admin
```

### Features

#### Device Management

**List All Devices**
```php
$devices = $genieacs->getDevices();
```

**Get Device Details**
```php
$device = $genieacs->getDevice($deviceId);
```

**Refresh Device**
```php
$genieacs->refreshDevice($deviceId);
```

#### Remote Control

**Reboot Device**
```php
$genieacs->rebootDevice($deviceId);
```

**Factory Reset**
```php
$genieacs->factoryReset($deviceId);
```

**Update WiFi Settings**
```php
$genieacs->updateWiFi($deviceId, [
    'ssid' => 'MyNetwork',
    'password' => 'SecurePassword123',
    'channel' => 'auto',
    'encryption' => 'WPA2-PSK'
]);
```

#### Provisioning

**Create Preset**
```php
$genieacs->createPreset([
    'name' => 'Default Config',
    'channel' => 'auto',
    'precondition' => '{
        "InternetGatewayDevice.DeviceInfo.ModelName": "HG8245H"
    }',
    'configurations' => [
        [
            'type' => 'value',
            'name' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
            'value' => 'WiFi-{{serialNumber}}'
        ]
    ]
]);
```

**Apply Preset to Device**
```php
$genieacs->applyPreset($deviceId, 'Default Config');
```

#### Monitoring

**Get Device Status**
```php
$status = $genieacs->getDeviceStatus($deviceId);
```

Response:
```json
{
    "device_id": "00259E-HG8245H-HWTC12345678",
    "status": "online",
    "model": "HG8245H",
    "manufacturer": "Huawei",
    "firmware": "V3R017C10S115",
    "ip_address": "192.168.1.100",
    "mac_address": "00:25:9E:12:34:56",
    "uptime": 86400,
    "last_inform": "2024-12-03T10:30:00Z",
    "wifi": {
        "ssid": "WiFi-Customer001",
        "channel": 6,
        "signal": -45
    }
}
```

### Dashboard Integration

#### CPE Management Page

**List View**
- Tabel semua CPE dengan status online/offline
- Filter berdasarkan model, status, customer
- Bulk actions (reboot, refresh)

**Detail View**
- Device information lengkap
- Real-time status monitoring
- Remote control buttons
- Configuration history
- Diagnostic tools

**Actions**
- Reboot device
- Factory reset
- Update WiFi settings
- Change admin password
- Port forwarding setup
- Firmware upgrade

### API Endpoints

#### Get All CPE
```
GET /api/cpe
```

#### Get CPE by Customer
```
GET /api/customers/{id}/cpe
```

#### Remote Reboot
```
POST /api/cpe/{id}/reboot
```

#### Update WiFi
```
POST /api/cpe/{id}/wifi
```

Request:
```json
{
    "ssid": "NewSSID",
    "password": "NewPassword123",
    "channel": "auto"
}
```

---

## 📱 WhatsApp Gateway Integration

### Overview
Integrasi WhatsApp untuk notifikasi otomatis ke pelanggan (invoice, reminder, konfirmasi pembayaran).

### Supported Providers
- Fonnte (https://fonnte.com)
- WaBlas (https://wablas.com)
- Custom API

### Configuration

Edit `.env`:
```env
WHATSAPP_API_URL=https://api.fonnte.com
WHATSAPP_API_KEY=your_api_key_here
WHATSAPP_SENDER=6281234567890
```

### Features

#### Send Invoice Notification
```php
$whatsapp->sendInvoiceNotification($customer, $invoice);
```

Message Template:
```
Halo *{nama}*,

Tagihan internet Anda telah terbit:

📋 *Invoice:* INV-000001
📦 *Paket:* Paket 10 Mbps
💰 *Total:* Rp 150.000
📅 *Jatuh Tempo:* 25 Dec 2025

Terima kasih,
*GEMBOK LARA*
```

#### Send Payment Confirmation
```php
$whatsapp->sendPaymentConfirmation($customer, $invoice);
```

#### Send Payment Reminder
```php
$whatsapp->sendPaymentReminder($customer, $invoice);
```

#### Send Voucher
```php
$whatsapp->sendVoucher($phone, $vouchers, $packageName);
```

#### Send Suspension Notice
```php
$whatsapp->sendSuspensionNotice($customer);
```

### Admin Dashboard

Access: `/admin/whatsapp`

Features:
- Connection status monitoring
- Send custom messages
- Quick actions (bulk invoice, bulk reminder)
- Message templates preview

### API Endpoints

#### Send Message
```
POST /api/whatsapp/send
```

Request:
```json
{
    "phone": "081234567890",
    "message": "Hello World"
}
```

#### Check Status
```
GET /api/whatsapp/status
```

---

## 💳 Payment Gateway Integration

### Overview
Integrasi payment gateway untuk pembayaran online (Midtrans & Xendit).

### Supported Gateways
- **Midtrans**: Credit Card, Bank Transfer, E-Wallet (GoPay, OVO), Convenience Store
- **Xendit**: Virtual Account, E-Wallet (OVO, DANA), QRIS, Retail Outlets

### Configuration

#### Midtrans
Edit `.env`:
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false
```

#### Xendit
Edit `.env`:
```env
XENDIT_SECRET_KEY=xnd_development_xxxxx
XENDIT_CALLBACK_TOKEN=your_callback_token
```

#### Default Gateway
```env
PAYMENT_DEFAULT_GATEWAY=midtrans
```

### Features

#### Create Payment Link
```php
$result = $paymentGateway->createPayment($invoice, $customer);
// Returns: payment_url, order_id
```

#### Create Snap Token (Midtrans)
```php
$result = $paymentGateway->createSnapToken($invoice, $customer);
// Returns: token, redirect_url
```

#### Send Payment Link via WhatsApp
```php
// Creates payment link and sends via WhatsApp
$paymentController->sendPaymentLink($invoice);
```

### Webhook Handlers

#### Midtrans Webhook
```
POST /api/webhooks/midtrans
```

Automatically:
- Verifies signature
- Updates invoice status
- Sends WhatsApp confirmation
- Activates customer (if suspended)

#### Xendit Webhook
```
POST /api/webhooks/xendit
```

Automatically:
- Verifies callback token
- Updates invoice status
- Sends WhatsApp confirmation
- Activates customer (if suspended)

### Admin Dashboard

Access: `/admin/payment`

Features:
- Gateway status overview
- Configuration guide
- Webhook URLs
- Default gateway selection

### Payment Flow

1. **Create Invoice** → Invoice created with status `unpaid`
2. **Generate Payment Link** → Payment URL created via gateway
3. **Send to Customer** → Link sent via WhatsApp
4. **Customer Pays** → Customer completes payment
5. **Webhook Received** → Gateway sends notification
6. **Invoice Updated** → Status changed to `paid`
7. **Customer Activated** → If suspended, reactivated
8. **Confirmation Sent** → WhatsApp confirmation sent

### API Endpoints

#### Create Payment
```
POST /admin/invoices/{invoice}/create-payment-link
```

#### Send Payment Link
```
POST /admin/invoices/{invoice}/send-payment-link
```

#### Check Status
```
GET /admin/payment/check-status?order_id=xxx&gateway=midtrans
```

---

## ⏰ Automated Billing

### Scheduled Tasks

Configure in `routes/console.php`:

```php
// Generate monthly invoices (1st of month at 00:01)
Schedule::command('billing:generate-invoices')
    ->monthlyOn(1, '00:01');

// Send reminders 3 days before due (daily at 09:00)
Schedule::command('billing:send-reminders --days=3')
    ->dailyAt('09:00');

// Send reminders 1 day before due (daily at 09:00)
Schedule::command('billing:send-reminders --days=1')
    ->dailyAt('09:00');

// Suspend overdue customers (daily at 01:00)
Schedule::command('billing:suspend-overdue --days=7')
    ->dailyAt('01:00');

// Sync Mikrotik users (hourly)
Schedule::command('mikrotik:sync-users --update')
    ->hourly();
```

### Setup Cron

Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Commands

```bash
# Generate invoices for current month
php artisan billing:generate-invoices

# Generate invoices for specific month
php artisan billing:generate-invoices --month=12 --year=2025

# Send reminders (3 days before due)
php artisan billing:send-reminders --days=3

# Suspend overdue (dry run)
php artisan billing:suspend-overdue --days=7 --dry-run

# Suspend overdue (execute)
php artisan billing:suspend-overdue --days=7

# Sync Mikrotik (create new)
php artisan mikrotik:sync-users --create

# Sync Mikrotik (update existing)
php artisan mikrotik:sync-users --update
```

---

## 🔐 Security Best Practices

### Mikrotik
1. Gunakan SSL/TLS untuk API connection
2. Buat user khusus dengan permission minimal
3. Whitelist IP GEMBOK LARA di firewall
4. Gunakan strong password
5. Enable audit logging

### GenieACS
1. Gunakan HTTPS untuk web interface
2. Change default credentials
3. Implement authentication di NBI
4. Restrict access dengan firewall
5. Regular backup MongoDB

---

## 🧪 Testing

### Mikrotik Connection Test
```bash
php artisan mikrotik:test
```

### GenieACS Connection Test
```bash
php artisan genieacs:test
```

### Full Integration Test
```bash
php artisan test --filter=IntegrationTest
```

---

## 📚 Resources

### Mikrotik
- [RouterOS API Documentation](https://wiki.mikrotik.com/wiki/Manual:API)
- [PPPoE Server Setup](https://wiki.mikrotik.com/wiki/Manual:PPPoE)
- [Hotspot Setup](https://wiki.mikrotik.com/wiki/Manual:Hotspot)

### GenieACS
- [Official Documentation](https://genieacs.com/docs/)
- [TR-069 Protocol](https://www.broadband-forum.org/technical/download/TR-069.pdf)
- [API Reference](https://github.com/genieacs/genieacs/wiki/API-Reference)

---

## 🆘 Troubleshooting

### Mikrotik Connection Failed
```bash
# Test connection
ping 192.168.1.1

# Test API port
telnet 192.168.1.1 8728

# Check logs
tail -f storage/logs/mikrotik.log
```

### GenieACS Device Not Connecting
```bash
# Check GenieACS logs
sudo journalctl -u genieacs-cwmp -f

# Verify CPE configuration
# ACS URL should be: http://your-server-ip:7547

# Test NBI API
curl http://localhost:7557/devices
```

---

## 💡 Tips & Tricks

1. **Batch Operations**: Gunakan queue untuk operasi bulk
2. **Caching**: Cache device status untuk performa
3. **Monitoring**: Setup monitoring untuk service availability
4. **Backup**: Regular backup konfigurasi Mikrotik & GenieACS
5. **Documentation**: Dokumentasikan custom presets & scripts

---

**Need Help?** Open an issue di [GitHub Issues](https://github.com/digitallenteranusa-bot/gembok-laravel/issues)


---

## 🔐 RADIUS Server Integration

### Overview
Integrasi dengan FreeRADIUS untuk autentikasi PPPoE sebagai alternatif atau complement dari Mikrotik.

### Prerequisites
- FreeRADIUS Server v3.x
- MySQL/MariaDB untuk RADIUS database
- PHP RADIUS extension (optional untuk CoA)

### Installation FreeRADIUS

```bash
# Ubuntu/Debian
sudo apt install freeradius freeradius-mysql freeradius-utils

# Enable MySQL module
cd /etc/freeradius/3.0/mods-enabled
sudo ln -s ../mods-available/sql sql

# Configure SQL module
sudo nano /etc/freeradius/3.0/mods-available/sql
```

### Configuration in GEMBOK LARA

Edit `.env`:
```env
RADIUS_ENABLED=true
RADIUS_DB_HOST=127.0.0.1
RADIUS_DB_PORT=3306
RADIUS_DB_DATABASE=radius
RADIUS_DB_USERNAME=radius
RADIUS_DB_PASSWORD=your_password
RADIUS_NAS_SECRET=testing123
```

### Features

#### User Management
```php
// Create user
$radius->createUser($username, $password, ['Framed-IP-Address' => '10.10.10.100']);

// Update password
$radius->updatePassword($username, $newPassword);

// Delete user
$radius->deleteUser($username);

// Suspend user
$radius->suspendUser($username);

// Unsuspend user
$radius->unsuspendUser($username);
```

#### Group/Profile Management
```php
// Create bandwidth profile
$radius->createGroup('10Mbps', '10M', '5M');

// Assign user to group
$radius->assignGroup($username, '10Mbps');
```

#### Monitoring
```php
// Get online users
$onlineUsers = $radius->getOnlineUsers();

// Get user history
$history = $radius->getUserHistory($username);

// Disconnect user (CoA)
$radius->disconnectUser($username);
```

### Admin Dashboard

Access: `/admin/radius`

Features:
- Online users monitoring
- User management (create/delete/suspend)
- Group/Profile management
- Session history

---

## 📊 SNMP Network Monitoring

### Overview
Monitor perangkat jaringan (router, switch, OLT, server) secara real-time menggunakan SNMP.

### Prerequisites
- PHP SNMP extension (`php-snmp`)
- SNMP enabled pada perangkat yang dimonitor

### Configuration

Edit `.env`:
```env
SNMP_ENABLED=true
SNMP_COMMUNITY=public
SNMP_VERSION=2c
SNMP_TIMEOUT=5
SNMP_RETRIES=2
```

### Features

#### System Information
```php
$snmp->getSystemInfo($host);
// Returns: description, uptime, name
```

#### Interface Statistics
```php
$snmp->getInterfaces($host);
// Returns: name, speed, status, in_octets, out_octets
```

#### Traffic Monitoring
```php
$snmp->getTrafficStats($host, $ifIndex);
// Returns: in_bps, out_bps, in_bytes, out_bytes
```

#### Resource Usage
```php
$snmp->getResourceUsage($host);
// Returns: cpu_usage, memory_total, memory_free, memory_percent
```

### Admin Dashboard

Access: `/admin/snmp`

Features:
- Device management (add/remove)
- Real-time status dashboard
- Interface statistics
- Traffic graphs
- Resource monitoring

---

## 👥 CRM Integration

### Overview
Integrasi dengan sistem CRM eksternal untuk customer relationship management.

### Supported Providers
- HubSpot
- Salesforce
- Zoho CRM

### Configuration

Edit `.env`:
```env
CRM_ENABLED=true
CRM_PROVIDER=hubspot
CRM_API_KEY=your_api_key
CRM_API_URL=https://api.hubapi.com
CRM_WEBHOOK_SECRET=your_webhook_secret
```

### Features

#### Contact Sync
```php
$crm->syncCustomer($customer);
// Syncs customer data to CRM as contact
```

#### Deal Creation
```php
$crm->createDeal($customer, $package, $amount);
// Creates deal/opportunity in CRM
```

#### Activity Logging
```php
$crm->logActivity($customerId, 'payment', 'Customer paid invoice INV-001');
```

### Admin Dashboard

Access: `/admin/integration/crm`

Features:
- Connection status
- Manual sync
- Bulk sync
- Test connection

---

## 💰 Accounting Integration

### Overview
Integrasi dengan software akuntansi untuk sinkronisasi invoice dan pembayaran.

### Supported Providers
- Accurate Online
- Jurnal.id (Mekari)
- Zahir

### Configuration

Edit `.env`:
```env
ACCOUNTING_ENABLED=true
ACCOUNTING_PROVIDER=accurate
ACCOUNTING_API_KEY=your_api_key
ACCOUNTING_API_URL=https://api.accurate.id
ACCOUNTING_COMPANY_ID=your_company_id
```

### Features

#### Customer Sync
```php
$accounting->syncCustomer($customer);
```

#### Invoice Sync
```php
$accounting->createInvoice($invoice);
```

#### Payment Recording
```php
$accounting->recordPayment($payment);
```

### Admin Dashboard

Access: `/admin/integration/accounting`

Features:
- Connection status
- Invoice sync
- Payment sync
- Bulk operations
- Test connection

---

**Last Updated**: December 4, 2025
