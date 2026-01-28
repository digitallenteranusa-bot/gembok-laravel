@extends('layouts.collector')

@section('title', 'Terima Pembayaran')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Terima Pembayaran</h1>
        <a href="{{ route('collector.invoices') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @if($invoice)
    <!-- Invoice Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Tagihan</h2>
        
        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="text-gray-500">No. Invoice</p>
                <p class="font-medium text-gray-800">{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <p class="text-gray-500">Jatuh Tempo</p>
                <p class="font-medium {{ $invoice->due_date < now() ? 'text-red-600' : 'text-gray-800' }}">
                    {{ $invoice->due_date->format('d M Y') }}
                </p>
            </div>
            <div>
                <p class="text-gray-500">Pelanggan</p>
                <p class="font-medium text-gray-800">{{ $invoice->customer->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Alamat</p>
                <p class="font-medium text-gray-800">{{ $invoice->customer->address ?? '-' }}</p>
            </div>
        </div>

        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <span class="text-blue-800 font-medium">Total Tagihan</span>
                <span class="text-2xl font-bold text-blue-600">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Form Pembayaran</h2>
        
        <form action="{{ route('collector.collect.process', $invoice) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Jumlah Bayar <span class="text-red-500">*</span></label>
                <input type="number" name="amount" value="{{ $invoice->total }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    min="1">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Metode Pembayaran <span class="text-red-500">*</span></label>
                <select name="payment_method" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="cash">Cash</option>
                    <option value="transfer">Transfer Bank</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Catatan</label>
                <textarea name="notes" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Catatan tambahan (opsional)"></textarea>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                <i class="fas fa-check mr-2"></i>Konfirmasi Pembayaran
            </button>
        </form>
    </div>
    @else
    <!-- Search Invoice -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Cari Tagihan</h2>
        
        <form action="{{ route('collector.invoices') }}" method="GET">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Nama / ID Pelanggan</label>
                <input type="text" name="search" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Masukkan nama atau ID pelanggan">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-search mr-2"></i>Cari Tagihan
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
