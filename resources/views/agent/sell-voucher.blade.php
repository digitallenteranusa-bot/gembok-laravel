@extends('layouts.agent')

@section('title', 'Jual Voucher')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Jual Voucher</h1>
        <a href="{{ route('agent.dashboard') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Package Selection -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Paket Voucher</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{ selected: {{ $selectedPackage ?? 'null' }} }">
                    @forelse($packages as $package)
                    <div class="border-2 rounded-lg p-4 cursor-pointer transition"
                         :class="selected == {{ $package->id }} ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300'"
                         @click="selected = {{ $package->id }}; document.getElementById('package_id').value = {{ $package->id }}">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-semibold text-gray-800">{{ $package->name }}</h3>
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded">{{ $package->duration }} Hari</span>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Harga Agent:</span>
                                <span class="font-medium text-emerald-600">Rp {{ number_format($package->agent_price, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Harga Jual:</span>
                                <span class="font-medium text-gray-800">Rp {{ number_format($package->customer_price, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-1 mt-1">
                                <span class="text-gray-500">Keuntungan:</span>
                                <span class="font-bold text-green-600">Rp {{ number_format($package->customer_price - $package->agent_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-2 text-center py-8 text-gray-500">
                        <i class="fas fa-ticket text-4xl mb-2"></i>
                        <p>Belum ada paket voucher tersedia</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sale Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Data Pembeli</h2>
                
                <form action="{{ route('agent.vouchers.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="package_id" id="package_id" value="{{ $selectedPackage }}">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Pembeli</label>
                        <input type="text" name="customer_name" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Nama pembeli (opsional)">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">No. WhatsApp <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="08xxxxxxxxxx">
                        <p class="text-xs text-gray-500 mt-1">Voucher akan dikirim ke nomor ini</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Saldo Anda:</span>
                            <span class="font-semibold text-emerald-600">Rp {{ number_format($agent->balance ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-semibold hover:bg-emerald-700 transition">
                        <i class="fas fa-shopping-cart mr-2"></i>Jual Voucher
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
