@extends('layouts.app')

@section('title', 'RADIUS Server')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">RADIUS Server Management</h1>
                    <p class="text-gray-600">Kelola autentikasi PPPoE via FreeRADIUS</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.radius.users') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-users mr-2"></i>Users
                    </a>
                    <a href="{{ route('admin.radius.groups') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-layer-group mr-2"></i>Groups
                    </a>
                </div>
            </div>

            @if(!$enabled)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                    <div>
                        <h3 class="text-yellow-800 font-medium">RADIUS Tidak Aktif</h3>
                        <p class="text-yellow-700 text-sm mt-1">
                            Aktifkan RADIUS dengan mengatur <code class="bg-yellow-100 px-1 rounded">RADIUS_ENABLED=true</code> di file .env
                        </p>
                    </div>
                </div>
            </div>
            @else

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-wifi text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Online Users</p>
                            <p class="text-2xl font-bold text-gray-800">{{ count($onlineUsers) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Online Users Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-signal text-green-500 mr-2"></i>Online Users
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NAS IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Framed IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Download</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Upload</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($onlineUsers as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $user->username }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->nasipaddress }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->framedipaddress }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->acctstarttime }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($user->acctinputoctets / 1048576, 2) }} MB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($user->acctoutputoctets / 1048576, 2) }} MB</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="{{ route('admin.radius.disconnect') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="username" value="{{ $user->username }}">
                                        <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Disconnect user ini?')">
                                            <i class="fas fa-plug"></i> Disconnect
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada user online</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
