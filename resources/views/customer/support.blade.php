@extends('layouts.customer')

@section('title', 'Bantuan')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Pusat Bantuan</h1>

    <!-- Contact Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Hubungi Kami</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="https://wa.me/6281234567890" target="_blank" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mr-4">
                    <i class="fab fa-whatsapp text-white text-2xl"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">WhatsApp</p>
                    <p class="text-sm text-gray-500">0812-3456-7890</p>
                </div>
            </a>
            
            <a href="tel:+6281234567890" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-phone text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">Telepon</p>
                    <p class="text-sm text-gray-500">0812-3456-7890</p>
                </div>
            </a>
        </div>
    </div>

    <!-- FAQ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Pertanyaan Umum</h2>
        
        <div class="space-y-4" x-data="{ open: null }">
            <div class="border border-gray-200 rounded-lg">
                <button @click="open = open === 1 ? null : 1" class="w-full flex items-center justify-between p-4 text-left">
                    <span class="font-medium text-gray-800">Bagaimana cara membayar tagihan?</span>
                    <i class="fas fa-chevron-down transition" :class="open === 1 ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === 1" x-collapse class="px-4 pb-4 text-gray-600 text-sm">
                    Anda dapat membayar tagihan melalui menu Tagihan, pilih invoice yang ingin dibayar, lalu klik tombol Bayar. Tersedia berbagai metode pembayaran seperti QRIS, Transfer Bank, dan E-Wallet.
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg">
                <button @click="open = open === 2 ? null : 2" class="w-full flex items-center justify-between p-4 text-left">
                    <span class="font-medium text-gray-800">Internet saya lambat, apa yang harus dilakukan?</span>
                    <i class="fas fa-chevron-down transition" :class="open === 2 ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === 2" x-collapse class="px-4 pb-4 text-gray-600 text-sm">
                    Coba restart router/modem Anda terlebih dahulu. Jika masih lambat, hubungi tim teknis kami melalui WhatsApp untuk pengecekan lebih lanjut.
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg">
                <button @click="open = open === 3 ? null : 3" class="w-full flex items-center justify-between p-4 text-left">
                    <span class="font-medium text-gray-800">Bagaimana cara upgrade paket?</span>
                    <i class="fas fa-chevron-down transition" :class="open === 3 ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === 3" x-collapse class="px-4 pb-4 text-gray-600 text-sm">
                    Untuk upgrade paket, silakan hubungi customer service kami melalui WhatsApp atau telepon. Tim kami akan membantu proses upgrade paket Anda.
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Ticket -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Kirim Tiket</h2>
        
        <form action="{{ route('customer.support.submit') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Subjek</label>
                <input type="text" name="subject" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                    placeholder="Masukkan subjek tiket">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Kategori</label>
                <select name="category" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    <option value="">Pilih kategori</option>
                    <option value="billing">Masalah Tagihan</option>
                    <option value="technical">Masalah Teknis</option>
                    <option value="installation">Instalasi</option>
                    <option value="complaint">Keluhan</option>
                    <option value="inquiry">Pertanyaan</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Pesan</label>
                <textarea name="message" rows="4" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                    placeholder="Jelaskan masalah Anda..."></textarea>
            </div>

            <button type="submit" class="w-full bg-cyan-600 text-white py-3 rounded-lg font-semibold hover:bg-cyan-700 transition">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Tiket
            </button>
        </form>
    </div>
</div>
@endsection
