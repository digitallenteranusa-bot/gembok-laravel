@extends('layouts.customer')

@section('title', 'Tagihan')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Tagihan Saya</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $invoice->invoice_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->period ?? $invoice->created_at->format('F Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-800">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($invoice->status == 'paid')
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Lunas</span>
                            @elseif($invoice->due_date < now())
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Jatuh Tempo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Belum Bayar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($invoice->status == 'unpaid')
                                <a href="{{ route('customer.pay', $invoice) }}" class="bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-cyan-700 transition">
                                    <i class="fas fa-credit-card mr-1"></i> Bayar
                                </a>
                            @else
                                <a href="{{ route('customer.invoices.show', $invoice) }}" class="text-cyan-600 hover:text-cyan-700">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Belum ada tagihan</p>
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
