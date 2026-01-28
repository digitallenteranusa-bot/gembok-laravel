@extends('layouts.app')

@section('title', 'Sync Logs')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Hotspot Sync Logs</h1>
            </div>
            <div class="bg-white rounded-xl shadow-sm mb-4 p-4">
                <form action="" method="GET" class="flex flex-wrap gap-4">
                    <select name="type" class="px-4 py-2 border rounded-lg">
                        <option value="">All Types</option>
                        <option value="profile" {{ request('type') == 'profile' ? 'selected' : '' }}>Profile</option>
                        <option value="voucher" {{ request('type') == 'voucher' ? 'selected' : '' }}>Voucher</option>
                    </select>
                    <select name="direction" class="px-4 py-2 border rounded-lg">
                        <option value="">All Directions</option>
                        <option value="pull" {{ request('direction') == 'pull' ? 'selected' : '' }}>Pull</option>
                        <option value="push" {{ request('direction') == 'push' ? 'selected' : '' }}>Push</option>
                        <option value="full" {{ request('direction') == 'full' ? 'selected' : '' }}>Full</option>
                    </select>
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-search mr-2"></i> Filter</button>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm capitalize">{{ $log->type }}</td>
                            <td class="px-6 py-4 text-sm">{!! $log->direction_label !!}</td>
                            <td class="px-6 py-4">{!! $log->status_badge !!}</td>
                            <td class="px-6 py-4 text-sm text-green-600">+{{ $log->created }}</td>
                            <td class="px-6 py-4 text-sm text-blue-600">~{{ $log->updated }}</td>
                            <td class="px-6 py-4 text-sm text-red-600">{{ $log->failed }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $log->user->name ?? 'System' }}</td>
                        </tr>
                        @if($log->error_message)
                        <tr class="bg-red-50">
                            <td colspan="8" class="px-6 py-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> {{ $log->error_message }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No logs found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($logs->hasPages())<div class="px-6 py-4">{{ $logs->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
