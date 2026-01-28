@extends('layouts.app')

@section('title', 'Hotspot Profiles')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Hotspot Profiles</h1>
                    <p class="text-gray-600 mt-1">Manage hotspot user profiles synced with Mikrotik</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.vouchers.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <a href="{{ route('admin.vouchers.hotspot.profiles.create') }}" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                        <i class="fas fa-plus mr-2"></i>Create Profile
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
                    <input type="hidden" name="type" value="profile">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i>Pull from Mikrotik
                    </button>
                </form>
            </div>

            <!-- Profiles Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Speed Limit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vouchers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($profiles as $profile)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $profile->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $profile->rate_limit ?: 'Unlimited' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $profile->session_timeout ?: '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($profile->price > 0)
                                        Rp {{ number_format($profile->price, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $profile->vouchers_count }}</td>
                                <td class="px-6 py-4">
                                    @if($profile->synced)
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Synced</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Not Synced</span>
                                    @endif
                                    @if(!$profile->is_active)
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full ml-1">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('admin.vouchers.hotspot.profiles.edit', $profile) }}" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.vouchers.hotspot.profiles.delete', $profile) }}" method="POST" class="inline" onsubmit="return confirm('Delete this profile?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-layer-group text-4xl mb-4 text-gray-300"></i>
                                    <p>No profiles found</p>
                                    <a href="{{ route('admin.vouchers.hotspot.profiles.create') }}" class="text-cyan-600 hover:text-cyan-800 mt-2 inline-block">Create profile →</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($profiles->hasPages())
                <div class="px-6 py-4 border-t">{{ $profiles->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
