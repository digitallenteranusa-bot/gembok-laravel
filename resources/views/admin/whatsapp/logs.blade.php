@extends('layouts.app')

@section('title', 'Riwayat Notifikasi WhatsApp')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Riwayat Notifikasi</h1>
                    <p class="text-gray-600 mt-1">Daftar semua notifikasi WhatsApp yang telah dikirim</p>
                </div>
                <a href="{{ route('admin.whatsapp.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-paper-plane text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Pesan</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Terkirim</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['sent']) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Gagal</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['failed']) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-cyan-100 rounded-lg">
                            <i class="fas fa-calendar-day text-cyan-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Hari Ini</p>
                            <p class="text-2xl font-bold text-cyan-600">{{ number_format($stats['today']) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                <form action="{{ route('admin.whatsapp.logs') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="No. HP / Nama" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Semua Status</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Gagal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Semua Tipe</option>
                            <option value="invoice" {{ request('type') == 'invoice' ? 'selected' : '' }}>Invoice</option>
                            <option value="reminder" {{ request('type') == 'reminder' ? 'selected' : '' }}>Reminder</option>
                            <option value="suspension" {{ request('type') == 'suspension' ? 'selected' : '' }}>Suspension</option>
                            <option value="voucher" {{ request('type') == 'voucher' ? 'selected' : '' }}>Voucher</option>
                            <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                            <i class="fas fa-search mr-1"></i>Filter
                        </button>
                        <a href="{{ route('admin.whatsapp.logs') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penerima</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pesan</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $log->customer->name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fab fa-whatsapp text-green-500 mr-1"></i>{{ $log->phone }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $typeColors = [
                                            'invoice' => 'bg-blue-100 text-blue-800',
                                            'reminder' => 'bg-yellow-100 text-yellow-800',
                                            'suspension' => 'bg-red-100 text-red-800',
                                            'voucher' => 'bg-purple-100 text-purple-800',
                                            'custom' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $typeLabels = [
                                            'invoice' => 'Invoice',
                                            'reminder' => 'Reminder',
                                            'suspension' => 'Suspension',
                                            'voucher' => 'Voucher',
                                            'custom' => 'Custom',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full {{ $typeColors[$log->type] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $typeLabels[$log->type] ?? ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if($log->invoice)
                                        <a href="{{ route('admin.invoices.show', $log->invoice) }}" class="text-cyan-600 hover:underline">
                                            {{ $log->invoice->invoice_number }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($log->status == 'sent')
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Terkirim
                                        </span>
                                    @elseif($log->status == 'failed')
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800" title="{{ $log->error_message }}">
                                            <i class="fas fa-times-circle mr-1"></i>Gagal
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <button onclick="showMessage({{ $log->id }})" class="text-cyan-600 hover:text-cyan-800 text-sm">
                                        <i class="fas fa-eye mr-1"></i>Lihat
                                    </button>
                                    <div id="message-{{ $log->id }}" class="hidden">{{ $log->message }}</div>
                                    @if($log->error_message)
                                        <div id="error-{{ $log->id }}" class="hidden">{{ $log->error_message }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    @if($log->status == 'failed')
                                        <button onclick="resendMessage({{ $log->id }})" class="text-green-600 hover:text-green-800" title="Kirim Ulang">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Belum ada riwayat notifikasi</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showMessage(id) {
    const message = document.getElementById('message-' + id).innerText;
    const errorEl = document.getElementById('error-' + id);
    const error = errorEl ? errorEl.innerText : null;
    
    let html = '<pre class="text-left text-sm whitespace-pre-wrap bg-gray-100 p-4 rounded-lg max-h-96 overflow-y-auto">' + escapeHtml(message) + '</pre>';
    
    if (error) {
        html += '<div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-left">';
        html += '<p class="text-sm font-medium text-red-800"><i class="fas fa-exclamation-triangle mr-1"></i>Error:</p>';
        html += '<p class="text-sm text-red-600">' + escapeHtml(error) + '</p>';
        html += '</div>';
    }
    
    Swal.fire({
        title: 'Detail Pesan',
        html: html,
        width: 600,
        showCloseButton: true,
        showConfirmButton: false
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function resendMessage(id) {
    Swal.fire({
        title: 'Kirim Ulang?',
        text: 'Pesan akan dikirim ulang ke penerima',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kirim Ulang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading('Mengirim ulang...');
            
            fetch('{{ url("admin/whatsapp/resend") }}/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                showError('Error: ' + error.message);
            });
        }
    });
}
</script>
@endpush
@endsection
