<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotSyncLog extends Model
{
    protected $fillable = [
        'type',
        'direction',
        'status',
        'total_items',
        'created',
        'updated',
        'deleted',
        'failed',
        'error_message',
        'details',
        'user_id',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'created' => 'integer',
        'updated' => 'integer',
        'deleted' => 'integer',
        'failed' => 'integer',
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'success' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Success</span>',
            'failed' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Failed</span>',
            'partial' => '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Partial</span>',
            default => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Unknown</span>',
        };
    }

    public function getDirectionLabelAttribute(): string
    {
        return match($this->direction) {
            'pull' => '⬇️ Pull (Mikrotik → Gembok)',
            'push' => '⬆️ Push (Gembok → Mikrotik)',
            'full' => '🔄 Full Sync',
            default => $this->direction,
        };
    }
}
