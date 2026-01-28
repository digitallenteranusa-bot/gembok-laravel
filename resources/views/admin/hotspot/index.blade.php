@extends('layouts.app')

@section('title', 'Hotspot Management')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Hotspot Management</h1>
                <p class="text-gray-600 mt-1">Manage hotspot profiles and vouchers with 2-way Mikrotik sync</p>
            </div>

            <div class="space-y-6">
                <!-- Connection Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            @if($mikrotikConnected)
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-link text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Mikrotik Connected</h3>
                                    <p class="text-sm text-gray-500">{{ $mikrotikIdentity ?? 'Unknown' }}</p>
                                </div>
                            @else
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-unlink text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Mikrotik Disconnected</h3>
                                    <p class="text-sm text-gray-500">Configure connection in Settings</p>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('admin.settings.mikrotik') }}" class="text-cyan-600 hover:text-cyan-700 text-sm font-medium">
                            <i class="fas fa-cog mr-1"></i> Settings
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Profiles -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Profiles</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_profiles'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $stats['active_profiles'] }} active</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-layer-group text-blue-600"></i>
                            </div>
                        </div>
                        <a href="{{ route('admin.hotspot.profiles') }}" class="mt-4 block text-sm text-blue-600 hover:text-blue-700">
                            Manage Profiles →
                        </a>
                    </div>

                    <!-- Total Vouchers -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Vouchers</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_vouchers'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $stats['unused_vouchers'] }} unused</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-ticket-alt text-green-600"></i>
                            </div>
                        </div>
                        <a href="{{ route('admin.hotspot.vouchers') }}" class="mt-4 block text-sm text-green-600 hover:text-green-700">
                            Manage Vouchers →
                        </a>
                    </div>

                    <!-- Used Vouchers -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Used Vouchers</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['used_vouchers'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $stats['expired_vouchers'] }} expired</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Unsynced -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Unsynced Items</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['unsynced_profiles'] + $stats['unsynced_vouchers'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $stats['unsynced_profiles'] }} profiles, {{ $stats['unsynced_vouchers'] }} vouchers</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-sync text-orange-600"></i>
                            </div>
                        </div>
                        <a href="{{ route('admin.hotspot.sync') }}" class="mt-4 block text-sm text-orange-600 hover:text-orange-700">
                            Sync Now →
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="{{ route('admin.hotspot.vouchers.generate') }}" class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl p-6 text-white hover:from-cyan-600 hover:to-blue-600 transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-plus text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">Generate Vouchers</h3>
                                <p class="text-sm text-white/80">Create new hotspot vouchers</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.hotspot.sync') }}" class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white hover:from-green-600 hover:to-emerald-600 transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-sync text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">Sync with Mikrotik</h3>
                                <p class="text-sm text-white/80">2-way sync profiles & vouchers</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.hotspot.profiles.create') }}" class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white hover:from-purple-600 hover:to-pink-600 transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-layer-group text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">Create Profile</h3>
                                <p class="text-sm text-white/80">Add new hotspot profile</p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Recent Sync Logs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Sync Activity</h3>
                            <a href="{{ route('admin.hotspot.logs') }}" class="text-sm text-cyan-600 hover:text-cyan-700">View All</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($recentLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 capitalize">{{ $log->type }}</td>
                                    <td class="px-6 py-4 text-sm">{!! $log->direction_label !!}</td>
                                    <td class="px-6 py-4">{!! $log->status_badge !!}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        +{{ $log->created }} / ~{{ $log->updated }} / -{{ $log->deleted }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $log->user->name ?? 'System' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No sync activity yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
