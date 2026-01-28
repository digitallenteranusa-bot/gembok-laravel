@extends('layouts.app')

@section('title', 'CPE Management')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, selectedDevices: [] }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">CPE Management</h1>
                        <p class="text-gray-600 mt-1">Remote manage customer modems via GenieACS</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($connected ?? false)
                            <span class="flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                GenieACS Connected
                            </span>
                        @else
                            <span class="flex items-center px-4 py-2 bg-red-100 text-red-800 rounded-lg">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Disconnected
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if(!($connected ?? false))
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">Connection Failed</h3>
                            <p class="text-red-700">{{ $error ?? 'Unable to connect to GenieACS. Please check your configuration.' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-cyan-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Total Devices</p>
                                <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                            </div>
                            <div class="h-14 w-14 bg-cyan-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-router text-cyan-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Online</p>
                                <p class="text-3xl font-bold text-green-600">{{ $stats['online'] ?? 0 }}</p>
                            </div>
                            <div class="h-14 w-14 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Offline</p>
                                <p class="text-3xl font-bold text-red-600">{{ $stats['offline'] ?? 0 }}</p>
                            </div>
                            <div class="h-14 w-14 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Bulk Actions -->
                <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <form method="GET" class="flex items-center space-x-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by device ID..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 w-64">
                            <button type="submit" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                        
                        <div class="flex items-center space-x-2">
                            <button onclick="bulkRefresh()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" x-show="selectedDevices.length > 0">
                                <i class="fas fa-sync-alt mr-2"></i>Bulk Refresh
                            </button>
                            <button onclick="bulkReboot()" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition" x-show="selectedDevices.length > 0">
                                <i class="fas fa-redo mr-2"></i>Bulk Reboot
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Devices Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" @change="selectedDevices = $event.target.checked ? {{ json_encode($devices->pluck('id')) }} : []" class="rounded">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Device</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Model</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">IP Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Last Seen</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($devices ?? [] as $device)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :value="'{{ $device['id'] }}'" x-model="selectedDevices" class="rounded">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $device['serial'] ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-500 font-mono">{{ Str::limit($device['id'], 30) }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="text-gray-900">{{ $device['model'] ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-500">{{ $device['manufacturer'] ?? '' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-sm text-gray-600">{{ $device['ip_address'] ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            @if($device['status'] === 'online')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                                    Online
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                                                    Offline
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $device['last_inform'] }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('admin.cpe.show', urlencode($device['id'])) }}" class="text-cyan-600 hover:text-cyan-800" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button onclick="refreshDevice('{{ $device['id'] }}')" class="text-blue-600 hover:text-blue-800" title="Refresh">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button onclick="rebootDevice('{{ $device['id'] }}')" class="text-yellow-600 hover:text-yellow-800" title="Reboot">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center">
                                            <i class="fas fa-router text-gray-300 text-5xl mb-4"></i>
                                            <p class="text-gray-500">No devices found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshDevice(deviceId) {
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/refresh`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => alert('Error: ' + error.message));
}

function rebootDevice(deviceId) {
    if (!confirm('Are you sure you want to reboot this device?')) return;
    
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/reboot`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => alert('Error: ' + error.message));
}

function bulkRefresh() {
    const devices = Alpine.raw(Alpine.$data(document.querySelector('[x-data]')).selectedDevices);
    if (devices.length === 0) return;
    
    fetch('/admin/cpe/bulk-refresh', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ device_ids: devices })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
}

function bulkReboot() {
    if (!confirm('Are you sure you want to reboot selected devices?')) return;
    
    const devices = Alpine.raw(Alpine.$data(document.querySelector('[x-data]')).selectedDevices);
    if (devices.length === 0) return;
    
    fetch('/admin/cpe/bulk-reboot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ device_ids: devices })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
}
</script>
@endpush
@endsection
