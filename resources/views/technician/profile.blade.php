@extends('layouts.technician')

@section('title', 'Profil')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center space-x-4 mb-6">
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-tools text-orange-600 text-3xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $technician->name ?? 'Technician' }}</h2>
                <p class="text-gray-500">{{ ucfirst($technician->role ?? 'Technician') }}</p>
                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ ($technician->status ?? '') == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ucfirst($technician->status ?? 'active') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="text-gray-500">Username</p>
                <p class="font-medium text-gray-800">{{ $technician->username ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">No. Telepon</p>
                <p class="font-medium text-gray-800">{{ $technician->phone ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Email</p>
                <p class="font-medium text-gray-800">{{ $technician->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Area</p>
                <p class="font-medium text-gray-800">{{ $technician->area ?? 'Semua Area' }}</p>
            </div>
        </div>

        <div class="bg-orange-50 rounded-lg p-4">
            <p class="text-sm text-orange-600 mb-1">Tugas Selesai Bulan Ini</p>
            <p class="text-2xl font-bold text-orange-700">0</p>
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
