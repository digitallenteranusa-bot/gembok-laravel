@extends('layouts.app')

@section('title', 'Hotspot Vouchers')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, selectedVouchers: [] }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Hotspot Vouchers</h1>
                    <p class="text-gray-600 mt-1">Manage hotspot vouchers synced with Mikrotik</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.vouchers.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <a href="{{ route('admin.vouchers.generate') }}?type=hotspot" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                        <i class="fas fa-plus mr-2"></i>Generate
                    </a>
                </div>
            </div>

            <!-- Mikrotik Status -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    @if($mikrotikConnected)
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-link text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Mikrotik Connected</p>
                            <p class="text-xs text-gray-500">Ready to sync</p>
                        </div>
                    @else
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-unlink text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Mikrotik Disconnected</p>
                            <p class="text-xs text-gray-500"><a href="{{ route('admin.settings.mikrotik') }}" class="text-cyan-600">Configure settings</a></p>
                        </div>
                    @endif
                </div>
                <form action="{{ route('admin.vouchers.hotspot.sync.do') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="direction" value="pull">
                    <input type="hidden" name="type" value="voucher">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i>Pull from Mikrotik
                    </button>
                </form>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm mb-4 p-4">
                <form action="" method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username..." class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                    <select name="profile_id" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                        <option value="">All Profiles</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}" {{ request('profile_id') == $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                        <option value="">All Status</option>
                        <option value="unused" {{ request('status') == 'unused' ? 'selected' : '' }}>Unused</option>
                        <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Used</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </form>
            </div>

            <!-- Vouchers Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Voucher List</h3>
                    <form action="{{ route('admin.vouchers.hotspot.vouchers.print') }}" method="POST" id="printForm">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm" x-show="selectedVouchers.length > 0">
                            <i class="fas fa-print mr-2"></i>Print Selected (<span x-text="selectedVouchers.length"></span>)
                        </button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" @change="selectedVouchers = $event.target.checked ? {{ $vouchers->pluck('id') }} : []" class="rounded">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Password</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profile</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Synced</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($vouchers as $voucher)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="ids[]" form="printForm" value="{{ $voucher->id }}" 
                                           x-model="selectedVouchers" :value="{{ $voucher->id }}" class="rounded">
                                </td>
                                <td class="px-6 py-4 font-mono font-medium text-gray-900">{{ $voucher->username }}</td>
                                <td class="px-6 py-4 font-mono text-gray-500">{{ $voucher->password }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $voucher->profile_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $voucher->limit_uptime ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($voucher->status === 'unused')
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Unused</span>
                                    @elseif($voucher->status === 'used')
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Used</span>
                                    @elseif($voucher->status === 'expired')
                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Expired</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">{{ ucfirst($voucher->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($voucher->synced)
                                        <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                    @else
                                        <span class="text-yellow-600"><i class="fas fa-exclamation-circle"></i></span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.vouchers.hotspot.vouchers.delete', $voucher) }}" method="POST" class="inline" onsubmit="return confirm('Delete this voucher?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-ticket-alt text-4xl mb-4 text-gray-300"></i>
                                    <p>No vouchers found</p>
                                    <a href="{{ route('admin.vouchers.generate') }}?type=hotspot" class="text-cyan-600 hover:text-cyan-800 mt-2 inline-block">Generate vouchers →</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($vouchers->hasPages())
                <div class="px-6 py-4 border-t">{{ $vouchers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
