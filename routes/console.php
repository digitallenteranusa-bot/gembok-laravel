<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| ISP Billing Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Generate monthly invoices on the 1st of each month at 00:01
Schedule::command('billing:generate-invoices')
    ->monthlyOn(1, '00:01')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/billing.log'));

// Send payment reminders 3 days before due date at 09:00
Schedule::command('billing:send-reminders --days=3')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminders.log'));

// Send payment reminders 1 day before due date at 09:00
Schedule::command('billing:send-reminders --days=1')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminders.log'));

// Suspend/isolate customers at 01:00
// - Customers with 3+ unpaid invoices (without installment plan) will be isolated
// - Customers with invoices overdue more than 7 days will be suspended
// - Recalculate all customer debts first to ensure accurate data
Schedule::command('billing:suspend-overdue --days=7 --min-invoices=3 --recalculate')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/suspension.log'));

// Reactivate customers who have paid at 02:00
Schedule::command('billing:reactivate-paid')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reactivation.log'));

// Send daily billing report at 18:00
Schedule::command('billing:report --period=daily --send')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports.log'));

// Send weekly billing report on Monday at 08:00
Schedule::command('billing:report --period=weekly --send')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports.log'));

// Send monthly billing report on the 1st at 08:00
Schedule::command('billing:report --period=monthly --send')
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports.log'));

// Sync Mikrotik users every hour
Schedule::command('mikrotik:sync-users --update')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/mikrotik.log'));

// Check IP Monitors every 5 minutes
Schedule::command('ip-monitor:check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ip-monitor.log'));
