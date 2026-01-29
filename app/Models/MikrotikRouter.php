<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MikrotikRouter extends Model
{
    protected $fillable = [
        'name',
        'identity',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'enabled',
        'is_default',
        'location',
        'notes',
        'last_connected_at',
        'last_connection_success',
        'last_connection_message',
    ];

    protected $casts = [
        'port' => 'integer',
        'use_ssl' => 'boolean',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_connection_success' => 'boolean',
    ];

    /**
     * Encrypt password when setting
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password when getting
     */
    public function getPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Return as-is if decryption fails (e.g., not encrypted yet)
                return $value;
            }
        }
        return $value;
    }

    /**
     * Get the customers for this router
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the default router
     */
    public static function getDefault()
    {
        return static::where('is_default', true)
            ->where('enabled', true)
            ->first();
    }

    /**
     * Get all enabled routers
     */
    public static function getEnabled()
    {
        return static::where('enabled', true)->get();
    }

    /**
     * Convert to connection config array for MikrotikService
     */
    public function toConnectionConfig(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'use_ssl' => $this->use_ssl,
        ];
    }

    /**
     * Update connection status after test
     */
    public function updateConnectionStatus(bool $success, ?string $message = null, ?string $identity = null): void
    {
        $this->update([
            'last_connected_at' => now(),
            'last_connection_success' => $success,
            'last_connection_message' => $message,
            'identity' => $identity ?? $this->identity,
        ]);
    }

    /**
     * Set this router as default (unset others)
     */
    public function setAsDefault(): void
    {
        // Unset other defaults
        static::where('id', '!=', $this->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Check if router is online (based on last connection)
     */
    public function isOnline(): bool
    {
        return $this->last_connection_success === true
            && $this->last_connected_at
            && $this->last_connected_at->diffInMinutes(now()) < 30;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->enabled) {
            return 'gray';
        }

        if ($this->isOnline()) {
            return 'green';
        }

        if ($this->last_connection_success === false) {
            return 'red';
        }

        return 'yellow';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->enabled) {
            return 'Disabled';
        }

        if ($this->isOnline()) {
            return 'Online';
        }

        if ($this->last_connection_success === false) {
            return 'Offline';
        }

        return 'Unknown';
    }

    /**
     * Scope for enabled routers
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope for default router
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
