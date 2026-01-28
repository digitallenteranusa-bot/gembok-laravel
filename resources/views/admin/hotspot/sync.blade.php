@extends('layouts.app')

@section('title', 'Sync Hotspot')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Sync Hotspot with Mikrotik</h1>
                <p class="text-gray-600 mt-1">2-way sync profiles and vouchers</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $profiles }}</p>
                    <p class="text-sm text-gray-500">Profiles in DB</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $vouchers }}</p>
                    <p class="text-sm text-gray-500">Vouchers in DB</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $unsyncedProfiles }}</p>
                    <p class="text-sm text-gray-500">Unsynced Profiles</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $unsyncedVouchers }}</p>
                    <p class="text-sm text-gray-500">Unsynced Vouchers</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Sync Options</h3>
                <form action="{{ route('admin.hotspot.sync.do') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="direction" value="pull" checked class="mr-3"> 
                            <div><p class="font-medium">⬇️ Pull</p><p class="text-xs text-gray-500">Mikrotik → Gembok</p></div>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="direction" value="push" class="mr-3">
                            <div><p class="font-medium">⬆️ Push</p><p class="text-xs text-gray-500">Gembok → Mikrotik</p></div>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="direction" value="full" class="mr-3">
                            <div><p class="font-medium">🔄 Full Sync</p><p class="text-xs text-gray-500">Both Ways</p></div>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="type" value="all" checked class="mr-3">
                            <div><p class="font-medium">All</p><p class="text-xs text-gray-500">Profiles & Vouchers</p></div>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="type" value="profile" class="mr-3">
                            <div><p class="font-medium">Profiles Only</p></div>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-cyan-500">
                            <input type="radio" name="type" value="voucher" class="mr-3">
                            <div><p class="font-medium">Vouchers Only</p></div>
                        </label>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700" {{ !$mikrotikConnected ? 'disabled' : '' }}>
                        <i class="fas fa-sync mr-2"></i> Start Sync
                    </button>
                    @if(!$mikrotikConnected)
                    <p class="text-red-500 text-sm mt-2 text-center">Mikrotik not connected. <a href="{{ route('admin.settings.mikrotik') }}" class="underline">Configure settings</a></p>
                    @endif
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b"><h3 class="font-semibold">Recent Sync Logs</h3></div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $log->created_at->format('d M H:i') }}</td>
                            <td class="px-6 py-4 text-sm capitalize">{{ $log->type }}</td>
                            <td class="px-6 py-4 text-sm">{!! $log->direction_label !!}</td>
                            <td class="px-6 py-4">{!! $log->status_badge !!}</td>
                            <td class="px-6 py-4 text-sm">+{{ $log->created }} / ~{{ $log->updated }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No sync logs yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
