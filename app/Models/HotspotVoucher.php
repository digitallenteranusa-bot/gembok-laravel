<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotVoucher extends Model
{
    protected $fillable = [
        'mikrotik_id',
        'profile_id',
        'username',
        'password',
        'profile_name',
        'limit_uptime',
        'limit_bytes_total',
        'limit_bytes_in',
        'limit_bytes_out',
        'server',
        'mac_address',
        'comment',
        'status',
        'used_at',
        'expires_at',
        'used_by_mac',
        'used_by_ip',
        'agent_id',
        'sold_by',
        'sold_at',
        'synced',
        'last_synced_at',
    ];

    protected $casts = [
        'limit_bytes_total' => 'integer',
        'limit_bytes_in' => 'integer',
        'limit_bytes_out' => 'integer',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'sold_at' => 'datetime',
        'synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HotspotProfile::class, 'profile_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function soldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'unused' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Unused</span>',
            'used' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Used</span>',
            'expired' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Expired</span>',
            'disabled' => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Disabled</span>',
            default => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Unknown</span>',
        };
    }

    public function getFormattedLimitAttribute(): string
    {
        if ($this->limit_uptime) {
            return $this->limit_uptime;
        }
        if ($this->limit_bytes_total) {
            return $this->formatBytes($this->limit_bytes_total);
        }
        return 'Unlimited';
    }

    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function scopeUnused($query)
    {
        return $query->where('status', 'unused');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeSynced($query)
    {
        return $query->where('synced', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('synced', false);
    }

    public function scopeByProfile($query, $profileId)
    {
        return $query->where('profile_id', $profileId);
    }
}
