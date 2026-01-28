@extends('layouts.app')

@section('title', 'CPE Details')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showWifiModal: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.cpe.index') }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900">{{ $status['serial_number'] ?? 'Device Details' }}</h1>
                        <p class="text-gray-600 mt-1">{{ $status['model'] ?? 'Unknown Model' }} - {{ $status['manufacturer'] ?? '' }}</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium {{ $status['status'] === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        <span class="w-2 h-2 rounded-full mr-2 {{ $status['status'] === 'online' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                        {{ ucfirst($status['status'] ?? 'unknown') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Device Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-info-circle mr-2 text-cyan-600"></i>
                            Device Information
                        </h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Serial Number</p>
                                <p class="font-medium text-gray-900 font-mono">{{ $status['serial_number'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Model</p>
                                <p class="font-medium text-gray-900">{{ $status['model'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Manufacturer</p>
                                <p class="font-medium text-gray-900">{{ $status['manufacturer'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Firmware</p>
                                <p class="font-medium text-gray-900">{{ $status['firmware'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">IP Address</p>
                                <p class="font-medium text-gray-900 font-mono">{{ $status['ip_address'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">MAC Address</p>
                                <p class="font-medium text-gray-900 font-mono">{{ $status['mac_address'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Uptime</p>
                                <p class="font-medium text-gray-900">{{ gmdate("H:i:s", $status['uptime'] ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Last Inform</p>
                                <p class="font-medium text-gray-900">{{ $status['last_inform'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- WiFi Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-wifi mr-2 text-blue-600"></i>
                                WiFi Settings
                            </h2>
                            <button @click="showWifiModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                <i class="fas fa-edit mr-2"></i>Edit WiFi
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">SSID</p>
                                <p class="font-medium text-gray-900">{{ $wifiInfo['ssid'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Status</p>
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ ($wifiInfo['enabled'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ($wifiInfo['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Channel</p>
                                <p class="font-medium text-gray-900">{{ $wifiInfo['channel'] ?? 'Auto' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Encryption</p>
                                <p class="font-medium text-gray-900">{{ $wifiInfo['encryption'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button onclick="refreshDevice()" class="w-full flex items-center justify-center bg-cyan-600 text-white px-4 py-3 rounded-lg hover:bg-cyan-700 transition">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh Device
                            </button>
                            <button onclick="rebootDevice()" class="w-full flex items-center justify-center bg-yellow-600 text-white px-4 py-3 rounded-lg hover:bg-yellow-700 transition">
                                <i class="fas fa-redo mr-2"></i>Reboot Device
                            </button>
                            <button onclick="factoryReset()" class="w-full flex items-center justify-center bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Factory Reset
                            </button>
                        </div>
                    </div>

                    <!-- Device Status -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Connection Status</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Status</span>
                                <span class="font-medium {{ $status['status'] === 'online' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ ucfirst($status['status'] ?? 'unknown') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Protocol</span>
                                <span class="font-medium text-gray-900">TR-069/CWMP</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WiFi Edit Modal -->
    <div x-show="showWifiModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" @click="showWifiModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Edit WiFi Settings</h3>
                <form id="wifiForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SSID</label>
                        <input type="text" name="ssid" value="{{ $wifiInfo['ssid'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" placeholder="Leave empty to keep current" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Channel</label>
                        <select name="channel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500">
                            <option value="0">Auto</option>
                            @for($i = 1; $i <= 13; $i++)
                                <option value="{{ $i }}" {{ ($wifiInfo['channel'] ?? 0) == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="enabled" id="wifi_enabled" {{ ($wifiInfo['enabled'] ?? false) ? 'checked' : '' }} class="rounded">
                        <label for="wifi_enabled" class="ml-2 text-sm text-gray-700">WiFi Enabled</label>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="showWifiModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const deviceId = '{{ $status['device_id'] ?? '' }}';

function refreshDevice() {
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/refresh`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => { alert(data.message); location.reload(); });
}

function rebootDevice() {
    if (!confirm('Reboot this device?')) return;
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/reboot`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => alert(data.message));
}

function factoryReset() {
    if (!confirm('WARNING: This will reset the device to factory settings. Continue?')) return;
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/factory-reset`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(data => alert(data.message));
}

document.getElementById('wifiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {
        ssid: formData.get('ssid'),
        password: formData.get('password') || undefined,
        channel: parseInt(formData.get('channel')),
        enabled: formData.has('enabled')
    };
    
    fetch(`/admin/cpe/${encodeURIComponent(deviceId)}/wifi`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
});
</script>
@endpush
@endsection
