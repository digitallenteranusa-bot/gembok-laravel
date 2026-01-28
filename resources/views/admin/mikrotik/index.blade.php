@extends('layouts.app')

@section('title', 'Mikrotik Management')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, activeTab: 'pppoe' }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Mikrotik Management</h1>
                        <p class="text-gray-600 mt-1">Monitor PPPoE & Hotspot users in real-time</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($connected ?? false)
                            <span class="flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                Connected
                            </span>
                        @else
                            <span class="flex items-center px-4 py-2 bg-red-100 text-red-800 rounded-lg">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Disconnected
                            </span>
                        @endif
                        <button onclick="refreshData()" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            @if(!($connected ?? false))
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">Connection Failed</h3>
                            <p class="text-red-700">{{ $error ?? 'Unable to connect to Mikrotik. Please check your configuration in .env file.' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-cyan-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">PPPoE Online</p>
                                <p class="text-3xl font-bold text-gray-900" id="pppoe-count">{{ $stats['pppoe_online'] ?? 0 }}</p>
                            </div>
                            <div class="h-14 w-14 bg-cyan-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-ethernet text-cyan-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Hotspot Online</p>
                                <p class="text-3xl font-bold text-gray-900" id="hotspot-count">{{ $stats['hotspot_online'] ?? 0 }}</p>
                            </div>
                            <div class="h-14 w-14 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-wifi text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">CPU Load</p>
                                <p class="text-3xl font-bold text-gray-900">{{ $stats['cpu_load'] ?? 0 }}%</p>
                            </div>
                            <div class="h-14 w-14 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-microchip text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Memory Usage</p>
                                <p class="text-3xl font-bold text-gray-900">{{ $stats['memory_usage'] ?? 0 }}%</p>
                            </div>
                            <div class="h-14 w-14 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-memory text-yellow-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button @click="activeTab = 'pppoe'" :class="activeTab === 'pppoe' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-medium text-sm transition">
                                <i class="fas fa-ethernet mr-2"></i>PPPoE Active ({{ count($pppoeActive ?? []) }})
                            </button>
                            <button @click="activeTab = 'hotspot'" :class="activeTab === 'hotspot' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-medium text-sm transition">
                                <i class="fas fa-wifi mr-2"></i>Hotspot Active ({{ count($hotspotActive ?? []) }})
                            </button>
                            <button @click="activeTab = 'system'" :class="activeTab === 'system' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-medium text-sm transition">
                                <i class="fas fa-server mr-2"></i>System Info
                            </button>
                        </nav>
                    </div>

                    <!-- PPPoE Tab -->
                    <div x-show="activeTab === 'pppoe'" class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Username</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">IP Address</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Caller ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Uptime</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($pppoeActive ?? [] as $session)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                                    <span class="font-medium text-gray-900">{{ $session['name'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $session['address'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600 font-mono text-sm">{{ $session['caller_id'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $session['uptime'] ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                <button onclick="disconnectUser('{{ $session['name'] }}', 'pppoe')" class="text-red-600 hover:text-red-800 transition" title="Disconnect">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                <i class="fas fa-ethernet text-4xl mb-2 text-gray-300"></i>
                                                <p>No active PPPoE sessions</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Hotspot Tab -->
                    <div x-show="activeTab === 'hotspot'" class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">IP Address</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">MAC Address</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Uptime</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Traffic</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($hotspotActive ?? [] as $session)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                                    <span class="font-medium text-gray-900">{{ $session['user'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $session['address'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600 font-mono text-sm">{{ $session['mac_address'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $session['uptime'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600 text-sm">
                                                <span class="text-green-600">↓ {{ formatBytes($session['bytes_in'] ?? 0) }}</span>
                                                <span class="text-blue-600 ml-2">↑ {{ formatBytes($session['bytes_out'] ?? 0) }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button onclick="disconnectUser('{{ $session['user'] }}', 'hotspot')" class="text-red-600 hover:text-red-800 transition" title="Disconnect">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                                <i class="fas fa-wifi text-4xl mb-2 text-gray-300"></i>
                                                <p>No active Hotspot sessions</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Tab -->
                    <div x-show="activeTab === 'system'" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle mr-2 text-cyan-600"></i>System Information
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Board Name</span>
                                        <span class="font-medium text-gray-900">{{ $systemResource['board-name'] ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Version</span>
                                        <span class="font-medium text-gray-900">{{ $systemResource['version'] ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Uptime</span>
                                        <span class="font-medium text-gray-900">{{ $systemResource['uptime'] ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Architecture</span>
                                        <span class="font-medium text-gray-900">{{ $systemResource['architecture-name'] ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">
                                    <i class="fas fa-chart-bar mr-2 text-cyan-600"></i>Resource Usage
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">CPU Load</span>
                                            <span class="font-medium text-gray-900">{{ $stats['cpu_load'] ?? 0 }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div class="bg-cyan-500 h-3 rounded-full" style="width: {{ $stats['cpu_load'] ?? 0 }}%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">Memory Usage</span>
                                            <span class="font-medium text-gray-900">{{ $stats['memory_usage'] ?? 0 }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div class="bg-blue-500 h-3 rounded-full" style="width: {{ $stats['memory_usage'] ?? 0 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshData() {
    location.reload();
}

function disconnectUser(username, type) {
    if (!confirm('Are you sure you want to disconnect ' + username + '?')) {
        return;
    }

    fetch('{{ route("admin.mikrotik.disconnect") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ username: username, type: type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User disconnected successfully');
            location.reload();
        } else {
            alert('Failed to disconnect user: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

// Auto refresh every 30 seconds
setInterval(function() {
    // You can implement AJAX refresh here
}, 30000);
</script>
@endpush
@endsection

@php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp
