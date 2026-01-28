@extends('layouts.app')

@section('title', isset($profile) ? 'Edit Hotspot Profile' : 'Create Hotspot Profile')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <a href="{{ route('admin.vouchers.hotspot.profiles') }}" class="text-gray-600 hover:text-gray-800 mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Profiles
                </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ isset($profile) ? 'Edit Profile' : 'Create Profile' }}</h1>
                <p class="text-gray-600 mt-1">{{ isset($profile) ? 'Update hotspot profile settings' : 'Create a new hotspot user profile' }}</p>
            </div>

            <div class="max-w-2xl">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <form action="{{ isset($profile) ? route('admin.vouchers.hotspot.profiles.update', $profile) : route('admin.vouchers.hotspot.profiles.store') }}" method="POST">
                        @csrf
                        @if(isset($profile)) @method('PUT') @endif

                        <div class="space-y-5">
                            <!-- Profile Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Name *</label>
                                <input type="text" name="name" value="{{ old('name', $profile->name ?? '') }}" required 
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 @error('name') border-red-500 @enderror"
                                       placeholder="e.g., 5Mbps-1Hour">
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Rate Limit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rate Limit (Speed)</label>
                                <input type="text" name="rate_limit" value="{{ old('rate_limit', $profile->rate_limit ?? '') }}" 
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500"
                                       placeholder="e.g., 5M/5M or 10M/10M">
                                <p class="text-xs text-gray-500 mt-1">Format: upload/download (e.g., 5M/5M for 5Mbps both ways)</p>
                            </div>

                            <!-- Shared Users & Session Timeout -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Shared Users</label>
                                    <input type="number" name="shared_users" value="{{ old('shared_users', $profile->shared_users ?? 1) }}" min="1" 
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                                    <p class="text-xs text-gray-500 mt-1">Max simultaneous logins</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Session Timeout</label>
                                    <input type="text" name="session_timeout" value="{{ old('session_timeout', $profile->session_timeout ?? '') }}" 
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500"
                                           placeholder="e.g., 1h, 3h, 1d">
                                    <p class="text-xs text-gray-500 mt-1">Max session duration</p>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Price (Rp)</label>
                                    <input type="number" name="price" value="{{ old('price', $profile->price ?? 0) }}" min="0" 
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Agent Price (Rp)</label>
                                    <input type="number" name="agent_price" value="{{ old('agent_price', $profile->agent_price ?? 0) }}" min="0" 
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                                </div>
                            </div>

                            <!-- Validity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Validity Period</label>
                                <input type="text" name="validity" value="{{ old('validity', $profile->validity ?? '') }}" 
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500"
                                       placeholder="e.g., 1h, 1d, 7d, 30d">
                                <p class="text-xs text-gray-500 mt-1">How long the voucher is valid after first use</p>
                            </div>

                            <!-- Options -->
                            <div class="flex items-center space-x-6 pt-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" 
                                           {{ old('is_active', $profile->is_active ?? true) ? 'checked' : '' }} 
                                           class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 mr-2">
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="sync_to_mikrotik" value="1" checked 
                                           class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 mr-2">
                                    <span class="text-sm text-gray-700">Sync to Mikrotik</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3 pt-4 border-t">
                            <a href="{{ route('admin.vouchers.hotspot.profiles') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                                <i class="fas fa-save mr-2"></i>{{ isset($profile) ? 'Update Profile' : 'Create Profile' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
