@extends('layouts.app')

@section('title', 'Device: ' . $host)

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Device: {{ $host }}</h1>
                    <p class="text-gray-600">Detail informasi perangkat</p>
                </div>
                <a href="{{ route('admin.snmp.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>

            @if(isset($systemInfo['error']))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                <p class="text-red-700">{{ $systemInfo['error'] }}</p>
            </div>
            @else

            <!-- System Info -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4"><i class="fas fa-info-circle text-blue-500 mr-2"></i>System Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-medium">{{ $systemInfo['name'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Uptime</p>
                        <p class="font-medium">{{ $systemInfo['uptime'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="font-medium text-sm">{{ Str::limit($systemInfo['description'] ?? '-', 100) }}</p>
                    </div>
                </div>
            </div>

            <!-- Resource Usage -->
            @if(!empty($resources))
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4"><i class="fas fa-microchip text-green-500 mr-2"></i>Resource Usage</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-2">CPU Usage</p>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full" style="width: {{ $resources['cpu_usage'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-right text-sm mt-1">{{ $resources['cpu_usage'] ?? 0 }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Memory Usage</p>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-green-600 h-4 rounded-full" style="width: {{ $resources['memory_percent'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-right text-sm mt-1">{{ $resources['memory_percent'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Interfaces -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold"><i class="fas fa-ethernet text-purple-500 mr-2"></i>Interfaces</h2></div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interface</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Speed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Out</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($interfaces as $iface)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $iface['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $iface['speed'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $iface['status'] == 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $iface['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($iface['in_octets'] / 1048576, 2) }} MB</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($iface['out_octets'] / 1048576, 2) }} MB</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No interfaces found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
