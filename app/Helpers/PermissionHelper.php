<?php

namespace App\Helpers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class PermissionHelper
{
    /**
     * Check if a role has specific permission
     *
     * @param string $role Role name (collector, technician, agent, customer)
     * @param string $permission Permission key
     * @return bool
     */
    public static function hasPermission($role, $permission)
    {
        $cacheKey = "role_permissions_{$role}";

        $permissions = Cache::remember($cacheKey, 3600, function () use ($role) {
            $value = AppSetting::getValue("role_permissions_{$role}", '[]');
            return json_decode($value, true) ?: self::getDefaultPermissions($role);
        });

        return in_array($permission, $permissions);
    }

    /**
     * Get all permissions for a role
     *
     * @param string $role
     * @return array
     */
    public static function getPermissions($role)
    {
        $cacheKey = "role_permissions_{$role}";

        return Cache::remember($cacheKey, 3600, function () use ($role) {
            $value = AppSetting::getValue("role_permissions_{$role}", '[]');
            $permissions = json_decode($value, true);
            return $permissions ?: self::getDefaultPermissions($role);
        });
    }

    /**
     * Get default permissions for a role
     *
     * @param string $role
     * @return array
     */
    public static function getDefaultPermissions($role)
    {
        $defaults = [
            'collector' => [
                'view_assigned_customers',
                'collect_payment',
                'view_invoice_detail',
                'view_payment_history',
                'view_commission',
                'view_customer_address',
                'view_customer_phone',
            ],
            'technician' => [
                'view_assigned_tasks',
                'update_task_status',
                'view_customer_info',
                'view_network_info',
                'view_map',
                'upload_photo',
            ],
            'agent' => [
                'sell_voucher',
                'view_balance',
                'request_topup',
                'view_transactions',
                'view_voucher_stock',
                'print_voucher',
            ],
            'customer' => [
                'view_invoices',
                'pay_online',
                'view_usage',
                'submit_ticket',
                'update_profile',
                'download_invoice',
            ],
        ];

        return $defaults[$role] ?? [];
    }

    /**
     * Clear permission cache for a role
     *
     * @param string|null $role If null, clear all role caches
     */
    public static function clearCache($role = null)
    {
        if ($role) {
            Cache::forget("role_permissions_{$role}");
        } else {
            foreach (['collector', 'technician', 'agent', 'customer'] as $r) {
                Cache::forget("role_permissions_{$r}");
            }
        }
    }
}
