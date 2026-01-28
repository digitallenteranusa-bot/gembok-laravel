@extends('layouts.app')

@section('title', 'Network Dashboard')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Network Dashboard</h1>
                    <p class="text-gray-600">Status perangkat jaringan</p>
                </div>
                <a href="{{ route('admin.snmp.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-cog mr-2"></i>Manage Devices
                </a>
            </div>

            @if(!$enabled)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                <p class="text-yellow-700">SNMP tidak aktif. Aktifkan di file .env</p>
            </div>
            @else

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg"><i class="fas fa-server text-blue-600 text-xl"></i></div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Devices</p>
                            <p class="text-2xl font-bold">{{ count($devices) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg"><i class="fas fa-check-circle text-green-600 text-xl"></i></div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Online</p>
                            <p class="text-2xl font-bold text-green-600">{{ collect($devices)->where('online', true)->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg"><i class="fas fa-times-circle text-red-600 text-xl"></i></div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Offline</p>
                            <p class="text-2xl font-bold text-red-600">{{ collect($devices)->where('online', false)->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg"><i class="fas fa-clock text-purple-600 text-xl"></i></div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Last Check</p>
                            <p class="text-lg font-bold">{{ now()->format('H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Devices Status -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold">Device Status</h2></div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uptime</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($devices as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $item['device']['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['device']['host'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $item['online'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $item['online'] ? 'Online' : 'Offline' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $item['system']['uptime'] ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.snmp.device', $item['device']['host']) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No devices configured</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
