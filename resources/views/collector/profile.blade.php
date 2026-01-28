@extends('layouts.collector')

@section('title', 'Profil')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center space-x-4 mb-6">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar text-blue-600 text-3xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $collector->name ?? 'Collector' }}</h2>
                <p class="text-gray-500">{{ $collector->area ?? 'Semua Area' }}</p>
                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ ($collector->status ?? '') == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ucfirst($collector->status ?? 'active') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="text-gray-500">Username</p>
                <p class="font-medium text-gray-800">{{ $collector->username ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">No. Telepon</p>
                <p class="font-medium text-gray-800">{{ $collector->phone ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Email</p>
                <p class="font-medium text-gray-800">{{ $collector->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Komisi</p>
                <p class="font-medium text-gray-800">{{ $collector->commission_rate ?? 2 }}%</p>
            </div>
        </div>

        <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-blue-600 mb-1">Total Komisi Bulan Ini</p>
            <p class="text-2xl font-bold text-blue-700">Rp {{ number_format($collector->payments()->whereMonth('created_at', now()->month)->sum('commission') ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <p class="text-yellow-800 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            Untuk mengubah data profil, silakan hubungi admin.
        </p>
    </div>
</div>
@endsection
