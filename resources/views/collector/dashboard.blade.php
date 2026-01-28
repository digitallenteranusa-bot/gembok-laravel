@extends('layouts.collector')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Selamat Datang, {{ $collector->name ?? 'Collector' }}!</h1>
                <p class="text-blue-100 mt-1">Area: {{ $collector->area ?? 'Semua Area' }}</p>
            </div>
            <div class="hidden md:block">
                <i class="fas fa-hand-holding-dollar text-6xl text-white/20"></i>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Target Hari Ini</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $todayTarget ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-bullseye text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Terkumpul Hari Ini</p>
                    <p class="text-2xl font-bold text-green-600">Rp {{ number_format($todayCollected ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Komisi Bulan Ini</p>
                    <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($monthCommission ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-coins text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Belum Bayar</p>
                    <p class="text-2xl font-bold text-red-600">{{ $unpaidCount ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('collector.invoices') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-file-invoice-dollar text-blue-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Tagihan</p>
        </a>
        <a href="{{ route('collector.collect') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Terima Bayar</p>
        </a>
        <a href="{{ route('collector.history') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-history text-purple-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Riwayat</p>
        </a>
        <a href="{{ route('collector.profile') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-cog text-gray-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Profil</p>
        </a>
    </div>

    <!-- Pending Invoices -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Tagihan Belum Bayar</h2>
            <a href="{{ route('collector.invoices') }}" class="text-blue-600 hover:text-blue-700 text-sm">Lihat Semua</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($pendingInvoices ?? [] as $invoice)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-invoice text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $invoice->customer->name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-500">{{ $invoice->invoice_number }} • {{ $invoice->customer->address ?? '' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-800">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
                    <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $invoice->isOverdue() ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $invoice->isOverdue() ? 'Jatuh Tempo' : 'Belum Bayar' }}
                    </span>
                </div>
                <a href="{{ route('collector.collect', $invoice->id) }}" class="ml-4 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                    <i class="fas fa-money-bill-wave mr-1"></i> Bayar
                </a>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                <p>Semua tagihan sudah terbayar!</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Today's Collection -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Penagihan Hari Ini</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($todayCollections ?? [] as $collection)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $collection->invoice->customer->name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-500">{{ $collection->created_at->format('H:i') }}</p>
                    </div>
                </div>
                <p class="font-semibold text-green-600">Rp {{ number_format($collection->amount, 0, ',', '.') }}</p>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>Belum ada penagihan hari ini</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
