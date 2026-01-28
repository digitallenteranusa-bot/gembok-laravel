@extends('layouts.customer')

@section('title', 'Penggunaan Internet')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 to-blue-700 rounded-2xl p-6 text-white">
        <h1 class="text-2xl font-bold">Penggunaan Internet</h1>
        <p class="text-cyan-100 mt-1">Monitor penggunaan bandwidth Anda</p>
    </div>

    <!-- Current Package -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Paket Aktif</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-cyan-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Nama Paket</p>
                <p class="text-xl font-bold text-cyan-700">{{ $customer->package->name ?? 'N/A' }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Kecepatan</p>
                <p class="text-xl font-bold text-blue-700">{{ $customer->package->speed ?? 'N/A' }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-xl font-bold {{ $customer->status == 'active' ? 'text-green-700' : 'text-red-700' }}">
                    {{ $customer->status == 'active' ? 'Aktif' : 'Tidak Aktif' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Connection Info -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Koneksi</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Username PPPoE</p>
                <p class="font-medium text-gray-800">{{ $customer->pppoe_username ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Tanggal Registrasi</p>
                <p class="font-medium text-gray-800">{{ $customer->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Usage Tips -->
    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
        <h3 class="font-semibold text-blue-800 mb-3"><i class="fas fa-lightbulb mr-2"></i>Tips Penggunaan</h3>
        <ul class="text-sm text-blue-700 space-y-2">
            <li>• Restart modem/router jika koneksi lambat</li>
            <li>• Pastikan tidak ada aplikasi yang menggunakan bandwidth berlebihan</li>
            <li>• Hubungi support jika masalah berlanjut</li>
        </ul>
    </div>
</div>
@endsection
