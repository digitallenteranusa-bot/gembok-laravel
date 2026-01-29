<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            // Clear cache for this setting
            Cache::forget("app_setting_{$key}");
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Show role & permission settings
     */
    public function roles()
    {
        // Get current permissions from settings
        $permissions = [
            'collector' => json_decode(AppSetting::getValue('role_permissions_collector', '[]'), true) ?: [],
            'technician' => json_decode(AppSetting::getValue('role_permissions_technician', '[]'), true) ?: [],
            'agent' => json_decode(AppSetting::getValue('role_permissions_agent', '[]'), true) ?: [],
            'customer' => json_decode(AppSetting::getValue('role_permissions_customer', '[]'), true) ?: [],
        ];

        // Set defaults if empty
        if (empty($permissions['collector'])) {
            $permissions['collector'] = [
                'view_assigned_customers',
                'collect_payment',
                'view_invoice_detail',
                'view_payment_history',
                'view_commission',
                'view_customer_address',
                'view_customer_phone',
            ];
        }

        if (empty($permissions['technician'])) {
            $permissions['technician'] = [
                'view_assigned_tasks',
                'update_task_status',
                'view_customer_info',
                'view_network_info',
                'view_map',
                'upload_photo',
            ];
        }

        if (empty($permissions['agent'])) {
            $permissions['agent'] = [
                'sell_voucher',
                'view_balance',
                'request_topup',
                'view_transactions',
                'view_voucher_stock',
                'print_voucher',
            ];
        }

        if (empty($permissions['customer'])) {
            $permissions['customer'] = [
                'view_invoices',
                'pay_online',
                'view_usage',
                'submit_ticket',
                'update_profile',
                'download_invoice',
            ];
        }

        return view('admin.settings.roles', compact('permissions'));
    }

    /**
     * Update role & permission settings
     */
    public function updateRoles(Request $request)
    {
        $roles = ['collector', 'technician', 'agent', 'customer'];

        foreach ($roles as $role) {
            $permissions = $request->input("{$role}_permissions", []);

            AppSetting::setValue(
                "role_permissions_{$role}",
                json_encode($permissions),
                'roles'
            );

            // Clear cache
            Cache::forget("role_permissions_{$role}");
        }

        return redirect()->back()->with('success', 'Pengaturan role berhasil disimpan!');
    }

    /**
     * Check if a role has specific permission
     */
    public static function hasPermission($role, $permission)
    {
        $cacheKey = "role_permissions_{$role}";

        $permissions = Cache::remember($cacheKey, 3600, function () use ($role) {
            return json_decode(AppSetting::getValue("role_permissions_{$role}", '[]'), true) ?: [];
        });

        return in_array($permission, $permissions);
    }
}
