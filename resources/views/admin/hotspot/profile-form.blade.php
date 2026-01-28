@extends('layouts.app')

@section('title', isset($profile) ? 'Edit Profile' : 'Create Profile')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ isset($profile) ? 'Edit Profile' : 'Create Profile' }}</h1>
            </div>
            <div class="max-w-2xl">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <form action="{{ isset($profile) ? route('admin.hotspot.profiles.update', $profile) : route('admin.hotspot.profiles.store') }}" method="POST">
                        @csrf
                        @if(isset($profile)) @method('PUT') @endif
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Name *</label>
                                <input type="text" name="name" value="{{ old('name', $profile->name ?? '') }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rate Limit</label>
                                <input type="text" name="rate_limit" value="{{ old('rate_limit', $profile->rate_limit ?? '') }}" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 5M/5M">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Shared Users</label>
                                    <input type="number" name="shared_users" value="{{ old('shared_users', $profile->shared_users ?? 1) }}" min="1" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Session Timeout</label>
                                    <input type="text" name="session_timeout" value="{{ old('session_timeout', $profile->session_timeout ?? '') }}" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 1h">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (Rp)</label>
                                    <input type="number" name="price" value="{{ old('price', $profile->price ?? 0) }}" min="0" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Agent Price (Rp)</label>
                                    <input type="number" name="agent_price" value="{{ old('agent_price', $profile->agent_price ?? 0) }}" min="0" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Validity</label>
                                <input type="text" name="validity" value="{{ old('validity', $profile->validity ?? '') }}" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 1h, 1d, 7d">
                            </div>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $profile->is_active ?? true) ? 'checked' : '' }} class="mr-2"> Active</label>
                                <label class="flex items-center"><input type="checkbox" name="sync_to_mikrotik" value="1" checked class="mr-2"> Sync to Mikrotik</label>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('admin.hotspot.profiles') }}" class="px-4 py-2 text-gray-700">Cancel</a>
                            <button type="submit" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">{{ isset($profile) ? 'Update' : 'Create' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
