<?php

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('appSetting')) {
    /**
     * Get app setting value with caching
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function appSetting($key, $default = null)
    {
        return Cache::remember("app_setting_{$key}", 3600, function () use ($key, $default) {
            return AppSetting::getValue($key, $default);
        });
    }
}

if (!function_exists('companyName')) {
    /**
     * Get company name from settings
     *
     * @return string
     */
    function companyName()
    {
        return appSetting('company_name', 'GEMBOK LARA');
    }
}

if (!function_exists('companyPhone')) {
    /**
     * Get company phone from settings
     *
     * @return string
     */
    function companyPhone()
    {
        return appSetting('company_phone', '');
    }
}

if (!function_exists('companyEmail')) {
    /**
     * Get company email from settings
     *
     * @return string
     */
    function companyEmail()
    {
        return appSetting('company_email', '');
    }
}

if (!function_exists('companyAddress')) {
    /**
     * Get company address from settings
     *
     * @return string
     */
    function companyAddress()
    {
        return appSetting('company_address', '');
    }
}

if (!function_exists('clearAppSettingsCache')) {
    /**
     * Clear all app settings cache
     *
     * @return void
     */
    function clearAppSettingsCache()
    {
        $keys = ['company_name', 'company_phone', 'company_email', 'company_address',
                 'default_commission_rate', 'tax_rate', 'currency', 'timezone'];

        foreach ($keys as $key) {
            Cache::forget("app_setting_{$key}");
        }
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Check if a role has specific permission
     *
     * @param string $role Role name (collector, technician, agent, customer)
     * @param string $permission Permission key
     * @return bool
     */
    function hasPermission($role, $permission)
    {
        return \App\Helpers\PermissionHelper::hasPermission($role, $permission);
    }
}

if (!function_exists('getPermissions')) {
    /**
     * Get all permissions for a role
     *
     * @param string $role
     * @return array
     */
    function getPermissions($role)
    {
        return \App\Helpers\PermissionHelper::getPermissions($role);
    }
}

if (!function_exists('canCollector')) {
    /**
     * Check if collector has permission
     *
     * @param string $permission
     * @return bool
     */
    function canCollector($permission)
    {
        return hasPermission('collector', $permission);
    }
}

if (!function_exists('canTechnician')) {
    /**
     * Check if technician has permission
     *
     * @param string $permission
     * @return bool
     */
    function canTechnician($permission)
    {
        return hasPermission('technician', $permission);
    }
}

if (!function_exists('canAgent')) {
    /**
     * Check if agent has permission
     *
     * @param string $permission
     * @return bool
     */
    function canAgent($permission)
    {
        return hasPermission('agent', $permission);
    }
}

if (!function_exists('canCustomer')) {
    /**
     * Check if customer has permission
     *
     * @param string $permission
     * @return bool
     */
    function canCustomer($permission)
    {
        return hasPermission('customer', $permission);
    }
}
