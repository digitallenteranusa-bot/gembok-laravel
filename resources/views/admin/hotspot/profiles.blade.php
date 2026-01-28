@extends('layouts.app')

@section('title', 'Hotspot Profiles')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')
    <div class="lg:pl-64">
        @include('admin.partials.topbar')
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Hotspot Profiles</h1>
                <p class="text-gray-600 mt-1">Manage hotspot user profiles</p>
            </div>
            <div class="flex justify-end mb-4 space-x-3">
                <form action="{{ route('admin.hotspot.sync.do') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="direction" value="pull">
                    <input type="hidden" name="type" value="profile">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i> Pull from Mikrotik
                    </button>
                </form>
                <a href="{{ route('admin.hotspot.profiles.create') }}" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                    <i class="fas fa-plus mr-2"></i> Create Profile
                </a>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Speed</th>
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
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $profile->formatted_price }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $profile->vouchers_count }}</td>
                            <td class="px-6 py-4">
                                @if($profile->synced)
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Synced</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Not Synced</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.hotspot.profiles.edit', $profile) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.hotspot.profiles.delete', $profile) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No profiles found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($profiles->hasPages())<div class="px-6 py-4">{{ $profiles->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
