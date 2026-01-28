@extends('layouts.app')

@section('title', 'Tambah Voucher Pricing')

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
                    <a href="{{ route('admin.vouchers.pricing') }}" class="hover:text-blue-600">Pricing</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900">Tambah</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Tambah Voucher Pricing</h1>
            </div>

            <div class="max-w-2xl">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <form action="{{ route('admin.vouchers.pricing.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Paket</label>
                            <input type="text" name="package_name" value="{{ old('package_name') }}" required
                                   placeholder="Contoh: Voucher 1 Jam"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('package_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Durasi (Jam)</label>
                            <input type="number" name="duration" value="{{ old('duration', 1) }}" required min="1"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('duration')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Customer (Rp)</label>
                                <input type="number" name="customer_price" value="{{ old('customer_price', 0) }}" required min="0"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Agent (Rp)</label>
                                <input type="number" name="agent_price" value="{{ old('agent_price', 0) }}" required min="0"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Komisi (Rp)</label>
                                <input type="number" name="commission_amount" value="{{ old('commission_amount', 0) }}" required min="0"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi (Opsional)</label>
                            <textarea name="description" rows="3" 
                                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">Aktif</span>
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-4 pt-4 border-t">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <a href="{{ route('admin.vouchers.pricing') }}" class="text-gray-600 hover:text-gray-900">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
