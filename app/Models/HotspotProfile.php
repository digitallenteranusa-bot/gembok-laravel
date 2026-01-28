<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotProfile extends Model
{
    protected $fillable = [
        'mikrotik_id',
        'name',
        'rate_limit',
        'upload_speed',
        'download_speed',
        'shared_users',
        'session_timeout',
        'idle_timeout',
        'keepalive_timeout',
        'address_pool',
        'price',
        'agent_price',
        'validity',
        'is_active',
        'synced',
        'last_synced_at',
    ];

    protected $casts = [
        'upload_speed' => 'integer',
        'download_speed' => 'integer',
        'shared_users' => 'integer',
        'price' => 'decimal:2',
        'agent_price' => 'decimal:2',
        'is_active' => 'boolean',
        'synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function vouchers(): HasMany
    {
        return $this->hasMany(HotspotVoucher::class, 'profile_id');
    }

    public function getSpeedLabelAttribute(): string
    {
        if ($this->upload_speed == $this->download_speed) {
            return $this->download_speed . ' Mbps';
        }
        return $this->upload_speed . '/' . $this->download_speed . ' Mbps';
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSynced($query)
    {
        return $query->where('synced', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('synced', false);
    }
}
