@extends('layouts.app')

@section('title', 'Hotspot Vouchers')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Hotspot Vouchers</h1>
                <p class="text-gray-600 mt-1">Manage hotspot vouchers</p>
            </div>
            <div class="flex justify-end mb-4 space-x-3">
                <form action="{{ route('admin.hotspot.sync.do') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="direction" value="pull">
                    <input type="hidden" name="type" value="voucher">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i> Pull from Mikrotik
                    </button>
                </form>
                <a href="{{ route('admin.hotspot.vouchers.generate') }}" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                    <i class="fas fa-plus mr-2"></i> Generate Vouchers
                </a>
            </div>
            <div class="bg-white rounded-xl shadow-sm mb-4 p-4">
                <form action="" method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="px-4 py-2 border rounded-lg">
                    <select name="profile_id" class="px-4 py-2 border rounded-lg">
                        <option value="">All Profiles</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}" {{ request('profile_id') == $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="unused" {{ request('status') == 'unused' ? 'selected' : '' }}>Unused</option>
                        <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Used</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fas fa-search mr-2"></i> Filter</button>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Password</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profile</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($vouchers as $voucher)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono font-medium">{{ $voucher->username }}</td>
                            <td class="px-6 py-4 font-mono text-gray-500">{{ $voucher->password }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $voucher->profile_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $voucher->formatted_limit }}</td>
                            <td class="px-6 py-4">{!! $voucher->status_badge !!}</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.hotspot.vouchers.delete', $voucher) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No vouchers found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($vouchers->hasPages())<div class="px-6 py-4">{{ $vouchers->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
