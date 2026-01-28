@extends('layouts.collector')

@section('title', 'Tagihan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Daftar Tagihan</h1>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <form action="" method="GET" class="flex space-x-2">
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Cari pelanggan...">
                <label class="flex items-center">
                    <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }}
                        class="mr-2 rounded text-blue-600">
                    <span class="text-sm text-gray-600">Jatuh Tempo</span>
                </label>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $invoice->customer->name ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ $invoice->customer->address ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->invoice_number }}</td>
                        <td class="px-6 py-4 text-sm {{ $invoice->due_date < now() ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                            {{ $invoice->due_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($invoice->due_date < now())
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Jatuh Tempo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Belum Bayar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('collector.collect', $invoice) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
                                <i class="fas fa-money-bill-wave mr-1"></i> Tagih
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                            <p>Tidak ada tagihan yang perlu ditagih</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
