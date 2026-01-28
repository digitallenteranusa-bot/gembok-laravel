@extends('layouts.customer')

@section('title', 'Profil')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>

    <!-- Account Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Akun</h2>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">ID Pelanggan</p>
                <p class="font-medium text-gray-800">{{ $customer->customer_id ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Status</p>
                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $customer->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ucfirst($customer->status ?? 'N/A') }}
                </span>
            </div>
            <div>
                <p class="text-gray-500">Paket</p>
                <p class="font-medium text-gray-800">{{ $customer->package->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Kecepatan</p>
                <p class="font-medium text-gray-800">{{ $customer->package->speed ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Bergabung Sejak</p>
                <p class="font-medium text-gray-800">{{ $customer->created_at ? $customer->created_at->format('d M Y') : 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Profil</h2>
        
        <form action="{{ route('customer.profile.update') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                <input type="text" value="{{ $customer->name ?? '' }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                <p class="text-xs text-gray-500 mt-1">Hubungi admin untuk mengubah nama</p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">No. Telepon</label>
                <input type="tel" name="phone" value="{{ $customer->phone ?? '' }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ $customer->email ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Alamat</label>
                <textarea disabled rows="3"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">{{ $customer->address ?? '' }}</textarea>
            </div>

            <hr class="my-6">

            <h3 class="text-lg font-semibold text-gray-800 mb-4">Ubah Password</h3>
            <p class="text-sm text-gray-500 mb-4">Kosongkan jika tidak ingin mengubah password</p>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
                <input type="password" name="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                    placeholder="Masukkan password baru">
            </div>

            <button type="submit" class="w-full bg-cyan-600 text-white py-3 rounded-lg font-semibold hover:bg-cyan-700 transition">
                <i class="fas fa-save mr-2"></i>Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endsection
