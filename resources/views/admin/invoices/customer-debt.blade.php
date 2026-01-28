@extends('layouts.app')

@section('title', 'Detail Hutang - ' . $customer->name)

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ showPaymentModal: false, selectedInvoice: null }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('admin.invoices.debt-report') }}" class="hover:text-gray-700">Laporan Hutang</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span>{{ $customer->name }}</span>
                </div>
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $customer->name }}</h1>
                        <p class="text-gray-600 mt-1">{{ $customer->phone ?? '-' }} | {{ $customer->email ?? '-' }}</p>
                    </div>
                    <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full
                        {{ $customer->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $customer->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-sm text-gray-500">Total Hutang</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($customer->total_debt, 0, ',', '.') }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-sm text-gray-500">Invoice Belum Bayar</div>
                    <div class="text-2xl font-bold text-orange-600 mt-1">{{ $customer->unpaid_invoices_count }} invoice</div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-sm text-gray-500">Status Cicilan</div>
                    <div class="text-2xl font-bold {{ $customer->has_installment_plan ? 'text-blue-600' : 'text-gray-400' }} mt-1">
                        {{ $customer->has_installment_plan ? 'Aktif' : 'Tidak Ada' }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-sm text-gray-500">Pembayaran Terakhir</div>
                    <div class="text-lg font-bold text-gray-600 mt-1">
                        {{ $customer->last_payment_date ? $customer->last_payment_date->format('d M Y') : '-' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Unpaid Invoices -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-orange-600">
                        <h3 class="text-lg font-semibold text-white">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Invoice Belum Bayar
                        </h3>
                    </div>

                    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                        @forelse($unpaidInvoices as $invoice)
                            <div class="p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800">{{ $invoice->invoice_number }}</span>
                                    @if($invoice->is_installment)
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                            Cicilan {{ $invoice->installment_number }}/{{ $invoice->total_installments }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 mb-2">{{ $invoice->description }}</div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm text-gray-500">Jatuh tempo:</span>
                                        <span class="text-sm {{ $invoice->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                            {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}
                                            @if($invoice->isOverdue())
                                                ({{ $invoice->due_date->diffInDays(now()) }} hari)
                                            @endif
                                        </span>
                                    </div>
                                    <span class="font-semibold text-red-600">
                                        Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                                    </span>
                                </div>
                                @if($invoice->paid_amount > 0)
                                    <div class="mt-2 text-xs text-green-600">
                                        Sudah dibayar: Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                                    </div>
                                @endif
                                <div class="mt-3 flex gap-2">
                                    <button @click="selectedInvoice = {{ $invoice->id }}; showPaymentModal = true"
                                        class="flex-1 bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-700 transition">
                                        <i class="fas fa-money-bill mr-1"></i>Bayar
                                    </button>
                                    @if(!$invoice->is_installment && $invoice->remaining_balance > 100000)
                                        <a href="{{ route('admin.invoices.installment.form', $invoice) }}"
                                           class="flex-1 bg-blue-600 text-white text-sm px-3 py-1.5 rounded hover:bg-blue-700 transition text-center">
                                            <i class="fas fa-calendar-alt mr-1"></i>Cicil
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-300 mb-2"></i>
                                <p>Tidak ada invoice belum bayar</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600">
                        <h3 class="text-lg font-semibold text-white">
                            <i class="fas fa-history mr-2"></i>Riwayat Pembayaran
                        </h3>
                    </div>

                    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                        @forelse($paymentHistory as $history)
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-gray-500">{{ $history->created_at->format('d M Y H:i') }}</span>
                                    <span class="font-semibold {{ $history->type === 'payment' ? 'text-green-600' : 'text-gray-600' }}">
                                        {{ $history->type === 'payment' ? '+' : '' }}Rp {{ number_format($history->amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-700">
                                    {{ $history->type_label }}
                                    @if($history->invoice)
                                        - {{ $history->invoice->invoice_number }}
                                    @endif
                                </div>
                                @if($history->notes)
                                    <div class="text-xs text-gray-500 mt-1">{{ $history->notes }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1">
                                    Saldo: Rp {{ number_format($history->balance_before, 0, ',', '.') }} → Rp {{ number_format($history->balance_after, 0, ',', '.') }}
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-receipt text-4xl text-gray-300 mb-2"></i>
                                <p>Belum ada riwayat pembayaran</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Installment Plan (if exists) -->
            @if($customer->activeInstallmentPlan)
                <div class="mt-6 bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600">
                        <h3 class="text-lg font-semibold text-white">
                            <i class="fas fa-calendar-check mr-2"></i>Rencana Cicilan Aktif
                        </h3>
                    </div>

                    <div class="p-6">
                        @php $plan = $customer->activeInstallmentPlan; @endphp
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <div class="text-sm text-gray-500">Total Cicilan</div>
                                <div class="font-semibold">{{ $plan->number_of_installments }}x @ Rp {{ number_format($plan->installment_amount, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Sudah Dibayar</div>
                                <div class="font-semibold text-green-600">{{ $plan->paid_installments }}/{{ $plan->number_of_installments }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Sisa</div>
                                <div class="font-semibold text-red-600">Rp {{ number_format($plan->remaining_amount, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Berakhir</div>
                                <div class="font-semibold">{{ $plan->end_date->format('d M Y') }}</div>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: {{ $plan->progress_percentage }}%"></div>
                        </div>
                        <div class="text-center text-sm text-gray-500 mt-1">{{ $plan->progress_percentage }}% selesai</div>
                    </div>
                </div>
            @endif

            <!-- Recent Paid Invoices -->
            @if($paidInvoices->count() > 0)
                <div class="mt-6 bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>Invoice Lunas Terakhir
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($paidInvoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ Str::limit($invoice->description, 50) }}</td>
                                        <td class="px-6 py-3 text-sm text-right text-green-600 font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ $invoice->paid_date ? $invoice->paid_date->format('d M Y') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showPaymentModal = false"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 rounded-t-lg">
                    <h3 class="text-xl font-semibold text-white">
                        <i class="fas fa-money-bill mr-2"></i>Catat Pembayaran
                    </h3>
                </div>

                <form method="POST" :action="'/admin/invoices/' + selectedInvoice + '/record-payment'" class="p-6">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Pembayaran *</label>
                        <input type="number" name="amount" required min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="Masukkan jumlah">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran *</label>
                        <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                            <option value="collector">Kolektor</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            placeholder="Catatan opsional..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="showPaymentModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
