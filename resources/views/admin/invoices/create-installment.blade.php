@extends('layouts.app')

@section('title', 'Buat Cicilan')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="installmentForm()">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('admin.invoices.debt-report') }}" class="hover:text-gray-700">Laporan Hutang</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('admin.invoices.customer-debt', $invoice->customer) }}" class="hover:text-gray-700">{{ $invoice->customer->name }}</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span>Cicilan</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Buat Rencana Cicilan</h1>
                <p class="text-gray-600 mt-1">Konversi invoice menjadi cicilan untuk pelanggan</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Invoice Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-cyan-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-file-invoice mr-2"></i>Invoice
                            </h3>
                        </div>

                        <div class="p-6 space-y-4">
                            <div>
                                <div class="text-sm text-gray-500">No. Invoice</div>
                                <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Pelanggan</div>
                                <div class="font-semibold">{{ $invoice->customer->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Deskripsi</div>
                                <div class="text-sm">{{ $invoice->description }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Sisa Tagihan</div>
                                <div class="text-2xl font-bold text-red-600">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</div>
                            </div>
                            @if($invoice->paid_amount > 0)
                                <div class="text-sm text-green-600">
                                    Sudah dibayar: Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Informasi Cicilan
                        </h4>
                        <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                            <li>Pelanggan dengan cicilan aktif tidak akan di-isolir</li>
                            <li>Invoice asli akan ditandai sebagai cicilan</li>
                            <li>Invoice cicilan baru akan dibuat sesuai jumlah termin</li>
                            <li>Pembayaran cicilan dilakukan per invoice</li>
                        </ul>
                    </div>
                </div>

                <!-- Installment Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-calendar-alt mr-2"></i>Pengaturan Cicilan
                            </h3>
                        </div>

                        <form method="POST" action="{{ route('admin.invoices.installment.store', $invoice) }}" class="p-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Cicilan *</label>
                                    <select name="number_of_installments" x-model="numberOfInstallments" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                        <option value="2">2x Cicilan</option>
                                        <option value="3">3x Cicilan</option>
                                        <option value="4">4x Cicilan</option>
                                        <option value="5">5x Cicilan</option>
                                        <option value="6">6x Cicilan</option>
                                        <option value="12">12x Cicilan</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mulai Tanggal *</label>
                                    <input type="date" name="start_date" x-model="startDate" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                                <textarea name="notes" rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Catatan atau alasan cicilan..."></textarea>
                            </div>

                            <!-- Preview -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h4 class="font-semibold text-gray-800 mb-4">Preview Cicilan</h4>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600" x-text="numberOfInstallments + 'x'"></div>
                                        <div class="text-sm text-gray-500">Cicilan</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600" x-text="formatRupiah(installmentAmount)"></div>
                                        <div class="text-sm text-gray-500">Per Bulan</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-purple-600" x-text="formatRupiah(totalAmount)"></div>
                                        <div class="text-sm text-gray-500">Total</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-600" x-text="endDate"></div>
                                        <div class="text-sm text-gray-500">Selesai</div>
                                    </div>
                                </div>

                                <!-- Schedule Preview -->
                                <div class="border-t pt-4">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Jadwal Pembayaran:</h5>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-32 overflow-y-auto">
                                        <template x-for="(schedule, index) in schedules" :key="index">
                                            <div class="bg-white border rounded p-2 text-center">
                                                <div class="text-xs text-gray-500">Cicilan <span x-text="index + 1"></span></div>
                                                <div class="text-sm font-medium" x-text="schedule.date"></div>
                                                <div class="text-xs text-green-600" x-text="formatRupiah(schedule.amount)"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <a href="{{ route('admin.invoices.customer-debt', $invoice->customer) }}"
                                   class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-center hover:bg-gray-50 transition">
                                    Batal
                                </a>
                                <button type="submit"
                                    class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-check mr-2"></i>Buat Cicilan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function installmentForm() {
    const totalDebt = {{ $invoice->remaining_balance }};

    return {
        numberOfInstallments: 3,
        startDate: new Date().toISOString().slice(0, 10),
        totalAmount: totalDebt,

        get installmentAmount() {
            return Math.ceil(this.totalAmount / this.numberOfInstallments);
        },

        get endDate() {
            if (!this.startDate) return '-';
            const date = new Date(this.startDate);
            date.setMonth(date.getMonth() + parseInt(this.numberOfInstallments) - 1);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        get schedules() {
            const schedules = [];
            if (!this.startDate) return schedules;

            let date = new Date(this.startDate);
            for (let i = 0; i < this.numberOfInstallments; i++) {
                schedules.push({
                    date: new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }),
                    amount: this.installmentAmount,
                });
                date.setMonth(date.getMonth() + 1);
            }
            return schedules;
        },

        formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
    }
}
</script>
@endsection
