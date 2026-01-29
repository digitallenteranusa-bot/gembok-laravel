@extends('layouts.app')

@section('title', 'Laporan Collector: ' . $collector->name)

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <a href="{{ route('admin.collectors.index') }}" class="hover:text-blue-600">Collectors</a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <span class="text-gray-900">Laporan</span>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900">Laporan Collector: {{ $collector->name }}</h1>
                        <p class="text-gray-600 mt-1">
                            @if($collector->phone) <i class="fas fa-phone mr-1"></i> {{ $collector->phone }} @endif
                            @if($collector->area) <i class="fas fa-map-marker-alt ml-3 mr-1"></i> {{ $collector->area }} @endif
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('admin.collectors.show', $collector) }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali
                        </a>
                        <a href="{{ route('admin.collectors.export', ['collector' => $collector, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Periode -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                <form action="{{ route('admin.collectors.report', $collector) }}" method="GET" class="flex items-end space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ $startDate }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="{{ $endDate }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    @if($startDate || $endDate)
                        <a href="{{ route('admin.collectors.report', $collector) }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Ringkasan Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total Pelanggan</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_customers'] }}</p>
                        </div>
                        <div class="h-14 w-14 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total Hutang Pelanggan</p>
                            <p class="text-3xl font-bold text-gray-900">Rp {{ number_format($stats['total_debt'], 0, ',', '.') }}</p>
                        </div>
                        <div class="h-14 w-14 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total Terkumpul</p>
                            <p class="text-3xl font-bold text-gray-900">Rp {{ number_format($stats['total_collection'], 0, ',', '.') }}</p>
                        </div>
                        <div class="h-14 w-14 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Jumlah Transaksi</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_transactions'] }}</p>
                        </div>
                        <div class="h-14 w-14 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-receipt text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Daftar Pelanggan & Hutang -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-4 bg-blue-50 border-b">
                        <h3 class="text-lg font-semibold text-blue-800">
                            <i class="fas fa-users mr-2"></i>Pelanggan & Hutang
                        </h3>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hutang</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($customers as $customer)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-blue-600 hover:underline font-medium">
                                                {{ $customer->name }}
                                            </a>
                                            @if($customer->phone)
                                                <div class="text-xs text-gray-500">{{ $customer->phone }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $customer->package?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded
                                                @if($customer->status === 'active') bg-green-100 text-green-800
                                                @elseif($customer->status === 'suspended') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($customer->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if($customer->total_debt > 0)
                                                <span class="text-red-600 font-medium">
                                                    Rp {{ number_format($customer->total_debt, 0, ',', '.') }}
                                                </span>
                                                <div class="text-xs text-gray-500">
                                                    {{ $customer->invoices->count() }} invoice
                                                </div>
                                            @else
                                                <span class="text-green-600">Lunas</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            Belum ada pelanggan yang di-assign ke collector ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($customers->count() > 0)
                                <tfoot class="bg-yellow-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-semibold">Total Hutang:</td>
                                        <td class="px-4 py-3 text-right font-bold text-red-600">
                                            Rp {{ number_format($stats['total_debt'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Riwayat Pembayaran -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-4 bg-green-50 border-b">
                        <h3 class="text-lg font-semibold text-green-800">
                            <i class="fas fa-money-bill-wave mr-2"></i>Riwayat Pembayaran
                            @if($startDate || $endDate)
                                <span class="text-sm font-normal">
                                    ({{ $startDate ? date('d/m/Y', strtotime($startDate)) : '' }}
                                    {{ $startDate && $endDate ? '-' : '' }}
                                    {{ $endDate ? date('d/m/Y', strtotime($endDate)) : '' }})
                                </span>
                            @endif
                        </h3>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($payments as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y') : '-' }}
                                            <div class="text-xs text-gray-400">
                                                {{ $payment->paid_at ? $payment->paid_at->format('H:i') : '' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900">
                                                {{ $payment->invoice?->customer?->name ?? '-' }}
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                {{ $payment->invoice?->invoice_number ?? '' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                                {{ $payment->method_label ?? ucfirst($payment->payment_method) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-green-600">
                                            Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            Belum ada pembayaran
                                            @if($startDate || $endDate)
                                                pada periode ini
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($payments->count() > 0)
                                <tfoot class="bg-green-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-semibold">Total Terkumpul:</td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">
                                            Rp {{ number_format($stats['total_collection'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
