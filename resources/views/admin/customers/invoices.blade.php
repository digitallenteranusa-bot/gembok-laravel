@extends('layouts.app')

@section('title', 'Invoices - ' . $customer->name)

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Invoice History</h1>
                        <p class="text-gray-600 mt-1">{{ $customer->name }} - {{ $customer->phone ?? 'No phone' }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-file-invoice text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Invoice</p>
                            <p class="text-xl font-bold text-gray-900">{{ $invoices->total() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Lunas</p>
                            <p class="text-xl font-bold text-green-600">{{ $customer->invoices()->where('status', 'paid')->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Belum Bayar</p>
                            <p class="text-xl font-bold text-yellow-600">{{ $customer->invoices()->where('status', 'unpaid')->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Tunggakan</p>
                            <p class="text-xl font-bold text-red-600">Rp {{ number_format($customer->invoices()->where('status', 'unpaid')->sum('amount'), 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900">Daftar Invoice</h2>
                    <a href="{{ route('admin.invoices.create', ['customer_id' => $customer->id]) }}" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition text-sm">
                        <i class="fas fa-plus mr-2"></i>Buat Invoice
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No. Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Paket</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jatuh Tempo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-cyan-600 hover:underline font-medium">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $invoice->period_start ? $invoice->period_start->format('d M') : '' }} - {{ $invoice->period_end ? $invoice->period_end->format('d M Y') : '' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $invoice->package->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($invoice->status == 'paid')
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Lunas</span>
                                    @elseif($invoice->status == 'unpaid')
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Belum Bayar</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-cyan-600 hover:text-cyan-800" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($invoice->status == 'unpaid')
                                        <form action="{{ route('admin.invoices.pay', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Tandai invoice ini sebagai lunas?')">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800" title="Tandai Lunas">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <a href="{{ route('admin.invoices.print', $invoice) }}" class="text-gray-600 hover:text-gray-800" title="Print" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-file-invoice text-4xl mb-2"></i>
                                    <p>Belum ada invoice</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($invoices->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $invoices->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
