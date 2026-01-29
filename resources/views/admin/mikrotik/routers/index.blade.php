@extends('layouts.app')

@section('title', 'Mikrotik Routers')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <a href="{{ route('admin.mikrotik.index') }}" class="hover:text-blue-600">Mikrotik</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-900">Routers</span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">Mikrotik Routers</h1>
                    <p class="text-gray-600 mt-1">Kelola multi router Mikrotik</p>
                </div>
                <a href="{{ route('admin.mikrotik.routers.create') }}" class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white px-4 py-2 rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Router
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Routers Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($routers as $router)
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 rounded-full flex items-center justify-center
                                        @if($router->status_color === 'green') bg-green-100
                                        @elseif($router->status_color === 'red') bg-red-100
                                        @elseif($router->status_color === 'yellow') bg-yellow-100
                                        @else bg-gray-100 @endif">
                                        <i class="fas fa-server text-xl
                                            @if($router->status_color === 'green') text-green-600
                                            @elseif($router->status_color === 'red') text-red-600
                                            @elseif($router->status_color === 'yellow') text-yellow-600
                                            @else text-gray-400 @endif"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $router->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $router->host }}:{{ $router->port }}</p>
                                    </div>
                                </div>
                                @if($router->is_default)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Default</span>
                                @endif
                            </div>

                            <div class="space-y-2 text-sm">
                                @if($router->identity)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Identity:</span>
                                        <span class="font-medium text-gray-900">{{ $router->identity }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status:</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-medium
                                        @if($router->status_color === 'green') bg-green-100 text-green-800
                                        @elseif($router->status_color === 'red') bg-red-100 text-red-800
                                        @elseif($router->status_color === 'yellow') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $router->status_text }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Customers:</span>
                                    <span class="font-medium text-gray-900">{{ $router->customers_count }}</span>
                                </div>
                                @if($router->location)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Location:</span>
                                        <span class="font-medium text-gray-900">{{ $router->location }}</span>
                                    </div>
                                @endif
                                @if($router->last_connected_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Last Check:</span>
                                        <span class="font-medium text-gray-900">{{ $router->last_connected_at->diffForHumans() }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                                <div class="flex space-x-2">
                                    <button onclick="testConnection({{ $router->id }})" class="text-cyan-600 hover:text-cyan-800 transition" title="Test Connection">
                                        <i class="fas fa-plug"></i>
                                    </button>
                                    <a href="{{ route('admin.mikrotik.routers.edit', $router) }}" class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(!$router->is_default)
                                        <button onclick="setDefault({{ $router->id }})" class="text-yellow-600 hover:text-yellow-800 transition" title="Set as Default">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    @endif
                                    @if($router->customers_count === 0)
                                        <form action="{{ route('admin.mikrotik.routers.destroy', $router) }}" method="POST" class="inline" onsubmit="return confirm('Hapus router ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 transition" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <a href="{{ route('admin.mikrotik.index', ['router_id' => $router->id]) }}" class="text-sm text-cyan-600 hover:text-cyan-800">
                                    Dashboard <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-xl shadow-md p-12 text-center">
                            <i class="fas fa-server text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-medium text-gray-900 mb-2">Belum Ada Router</h3>
                            <p class="text-gray-500 mb-4">Tambahkan router Mikrotik pertama Anda</p>
                            <a href="{{ route('admin.mikrotik.routers.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Tambah Router
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testConnection(routerId) {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`{{ url('admin/mikrotik/routers') }}/${routerId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Koneksi berhasil!' + (data.identity ? ' Identity: ' + data.identity : ''));
        } else {
            alert('Gagal: ' + data.message);
        }
        location.reload();
    })
    .catch(error => {
        alert('Error: ' + error.message);
        location.reload();
    });
}

function setDefault(routerId) {
    if (!confirm('Jadikan router ini sebagai default?')) return;

    fetch(`{{ url('admin/mikrotik/routers') }}/${routerId}/set-default`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
@endpush
@endsection
