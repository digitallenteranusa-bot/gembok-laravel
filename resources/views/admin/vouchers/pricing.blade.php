@extends('layouts.app')

@section('title', 'Voucher Pricing')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('admin.vouchers.index') }}" class="hover:text-blue-600">Vouchers</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900">Pricing</span>
                </div>
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-900">Voucher Pricing</h1>
                    <a href="{{ route('admin.vouchers.pricing.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Tambah Pricing
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if($pricings->isEmpty())
                <!-- Empty State -->
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-ticket-alt text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum Ada Data Pricing</h3>
                    <p class="text-gray-500 mb-6">Tambahkan pricing voucher untuk mulai menjual voucher hotspot.</p>
                    <div class="flex justify-center space-x-4">
                        <a href="{{ route('admin.vouchers.pricing.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Tambah Pricing Manual
                        </a>
                        <form action="{{ route('admin.vouchers.pricing.seed') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-magic mr-2"></i>Generate Data Sample
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <!-- Pricing Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($pricings as $pricing)
                        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-gray-900">{{ $pricing->package_name }}</h3>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $pricing->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $pricing->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Durasi</span>
                                        <span class="font-medium">{{ $pricing->duration }} Jam</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Harga Customer</span>
                                        <span class="font-bold text-blue-600">Rp {{ number_format($pricing->customer_price, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Harga Agent</span>
                                        <span class="font-bold text-cyan-600">Rp {{ number_format($pricing->agent_price, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Komisi</span>
                                        <span class="font-medium text-green-600">Rp {{ number_format($pricing->commission_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <form action="{{ route('admin.vouchers.pricing.update') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $pricing->id }}">
                                    
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Harga Cust.</label>
                                            <input type="number" name="customer_price" value="{{ $pricing->customer_price }}" class="w-full px-2 py-1.5 border rounded text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Harga Agent</label>
                                            <input type="number" name="agent_price" value="{{ $pricing->agent_price }}" class="w-full px-2 py-1.5 border rounded text-sm">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Komisi</label>
                                        <input type="number" name="commission_amount" value="{{ $pricing->commission_amount }}" class="w-full px-2 py-1.5 border rounded text-sm">
                                    </div>

                                    <div class="flex items-center justify-between pt-3 border-t">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_active" value="1" {{ $pricing->is_active ? 'checked' : '' }} class="rounded text-blue-600">
                                            <span class="ml-2 text-xs text-gray-600">Active</span>
                                        </label>
                                        <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700 transition">
                                            Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
