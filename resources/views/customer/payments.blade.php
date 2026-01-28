@extends('layouts.customer')

@section('title', 'Riwayat Pembayaran')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Riwayat Pembayaran</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $payment->invoice_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->period ?? $payment->created_at->format('F Y') }}</td>
                        <td class="px-6 py-4 font-semibold text-green-600">Rp {{ number_format($payment->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs rounded-full 
                                {{ $payment->payment_method == 'cash' ? 'bg-green-100 text-green-700' : 
                                   ($payment->payment_method == 'transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                {{ ucfirst($payment->payment_method ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Belum ada riwayat pembayaran</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
