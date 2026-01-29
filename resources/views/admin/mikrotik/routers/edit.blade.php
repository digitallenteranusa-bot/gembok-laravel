@extends('layouts.app')

@section('title', 'Edit Router')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showPassword: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('admin.mikrotik.index') }}" class="hover:text-blue-600">Mikrotik</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('admin.mikrotik.routers.index') }}" class="hover:text-blue-600">Routers</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900">Edit</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Router: {{ $router->name }}</h1>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-md p-6 max-w-2xl">
                <form action="{{ route('admin.mikrotik.routers.update', $router) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Connection Status -->
                        @if($router->last_connected_at)
                            <div class="p-4 rounded-lg
                                @if($router->last_connection_success) bg-green-50 border border-green-200
                                @else bg-red-50 border border-red-200 @endif">
                                <div class="flex items-center">
                                    <i class="fas @if($router->last_connection_success) fa-check-circle text-green-600 @else fa-times-circle text-red-600 @endif text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium @if($router->last_connection_success) text-green-800 @else text-red-800 @endif">
                                            {{ $router->last_connection_success ? 'Terakhir terhubung' : 'Koneksi gagal' }}
                                        </p>
                                        <p class="text-sm @if($router->last_connection_success) text-green-600 @else text-red-600 @endif">
                                            {{ $router->last_connected_at->diffForHumans() }}
                                            @if($router->last_connection_message)
                                                - {{ $router->last_connection_message }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tag mr-2 text-blue-600"></i>Nama Router *
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $router->name) }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                placeholder="Contoh: Router Area A">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Connection Details -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label for="host" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-network-wired mr-2 text-blue-600"></i>Host / IP Address *
                                </label>
                                <input type="text" name="host" id="host" value="{{ old('host', $router->host) }}" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('host') border-red-500 @enderror"
                                    placeholder="192.168.1.1">
                                @error('host')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="port" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-plug mr-2 text-blue-600"></i>Port
                                </label>
                                <input type="number" name="port" id="port" value="{{ old('port', $router->port) }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('port') border-red-500 @enderror"
                                    placeholder="8728">
                                @error('port')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Credentials -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2 text-blue-600"></i>Username *
                                </label>
                                <input type="text" name="username" id="username" value="{{ old('username', $router->username) }}" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('username') border-red-500 @enderror"
                                    placeholder="admin">
                                @error('username')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2 text-blue-600"></i>Password
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                                        placeholder="Kosongkan jika tidak diubah">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="enabled" id="enabled" value="1" {{ old('enabled', $router->enabled) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="enabled" class="ml-2 text-sm text-gray-700">Enabled</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default', $router->is_default) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="is_default" class="ml-2 text-sm text-gray-700">Set as Default</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="use_ssl" id="use_ssl" value="1" {{ old('use_ssl', $router->use_ssl) ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="use_ssl" class="ml-2 text-sm text-gray-700">Use SSL</label>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>Lokasi
                            </label>
                            <input type="text" name="location" id="location" value="{{ old('location', $router->location) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: Gedung A, Lantai 1">
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-blue-600"></i>Catatan
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Catatan tambahan tentang router ini">{{ old('notes', $router->notes) }}</textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex items-center justify-end space-x-4">
                        <a href="{{ route('admin.mikrotik.routers.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Batal
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg hover:from-blue-600 hover:to-cyan-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
