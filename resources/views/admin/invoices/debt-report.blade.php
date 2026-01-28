@extends('layouts.app')

@section('title', 'Laporan Hutang')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Laporan Hutang Pelanggan</h1>
                    <p class="text-gray-600 mt-1">Monitoring piutang dan hutang pelanggan</p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.invoices.recalculate-debts') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition">
                            <i class="fas fa-sync-alt mr-2"></i>Recalculate
                        </button>
                    </form>
                    <a href="{{ route('admin.invoices.bulk.form') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-file-invoice mr-2"></i>Generate Invoice
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Piutang</p>
                            <p class="text-2xl font-bold text-red-600">Rp {{ number_format($stats['total_debt'], 0, ',', '.') }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-red-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pelanggan Berhutang</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $stats['total_customers_with_debt'] }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                            <i class="fas fa-users text-orange-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Hutang 3+ Invoice</p>
                            <p class="text-2xl font-bold text-red-600">{{ $stats['customers_3_or_more'] }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Kandidat isolir</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cicilan Aktif</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $stats['customers_with_installment'] }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-calendar-check text-blue-600"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Tetap aktif</p>
                </div>
            </div>

            <!-- Aging Report -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-clock mr-2 text-purple-600"></i>Umur Piutang (Aging)
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-lg font-bold text-green-600">Rp {{ number_format($stats['aging']['current'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">Belum Jatuh Tempo</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="text-lg font-bold text-yellow-600">Rp {{ number_format($stats['aging']['1-30'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">1-30 Hari</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <div class="text-lg font-bold text-orange-600">Rp {{ number_format($stats['aging']['31-60'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">31-60 Hari</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="text-lg font-bold text-red-600">Rp {{ number_format($stats['aging']['61-90'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">61-90 Hari</div>
                    </div>
                    <div class="text-center p-4 bg-red-100 rounded-lg">
                        <div class="text-lg font-bold text-red-700">Rp {{ number_format($stats['aging']['over_90'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">> 90 Hari</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('admin.invoices.debt-report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Paket</label>
                        <select name="package_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Semua Paket</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Min. Hutang</label>
                        <input type="number" name="min_debt" value="{{ request('min_debt') }}" placeholder="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Min. Invoice Belum Bayar</label>
                        <select name="min_unpaid_count" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Semua</option>
                            <option value="1" {{ request('min_unpaid_count') == '1' ? 'selected' : '' }}>1+</option>
                            <option value="2" {{ request('min_unpaid_count') == '2' ? 'selected' : '' }}>2+</option>
                            <option value="3" {{ request('min_unpaid_count') == '3' ? 'selected' : '' }}>3+ (Kandidat Isolir)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Customer Debt Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Invoice Belum Bayar</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Hutang</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cicilan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($customers as $customer)
                                <tr class="hover:bg-gray-50 {{ $customer->unpaid_invoices_count >= 3 && !$customer->has_installment_plan ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $customer->phone ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $customer->package->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                            {{ $customer->unpaid_invoices_count >= 3 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $customer->unpaid_invoices_count }} invoice
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-lg font-semibold text-red-600">
                                            Rp {{ number_format($customer->total_debt, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $customer->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $customer->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst($customer->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($customer->has_installment_plan)
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <i class="fas fa-check mr-1"></i>Aktif
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.invoices.customer-debt', $customer) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye mr-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-check-circle text-4xl mb-4 text-green-300"></i>
                                        <p>Tidak ada pelanggan dengan hutang</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 px-6 py-4">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
