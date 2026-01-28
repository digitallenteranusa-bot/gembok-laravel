@extends('layouts.app')

@section('title', 'Generate Invoice Massal')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="bulkInvoice()">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('admin.invoices.index') }}" class="hover:text-gray-700">Invoice</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span>Generate Massal</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Generate Invoice Massal</h1>
                <p class="text-gray-600 mt-1">Buat invoice untuk semua pelanggan aktif sekaligus</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-cyan-600">
                            <h3 class="text-xl font-semibold text-white">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>Pengaturan Invoice
                            </h3>
                        </div>

                        <form @submit.prevent="previewInvoices" class="p-6 space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bulan Tagihan *</label>
                                    <input type="month" x-model="form.billing_month" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jatuh Tempo *</label>
                                    <input type="date" x-model="form.due_date" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Paket (opsional)</label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                    @foreach($packages as $package)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" x-model="form.package_ids" value="{{ $package->id }}"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm">{{ $package->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Kosongkan untuk semua paket</p>
                            </div>

                            <div>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.send_notification"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Kirim notifikasi WhatsApp ke pelanggan</span>
                                </label>
                            </div>

                            <div class="flex gap-3">
                                <button type="submit" :disabled="loading"
                                    class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                    <i class="fas fa-search mr-2"></i>Preview
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preview Results -->
                    <div x-show="preview" x-cloak class="mt-6 bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600">
                            <h3 class="text-xl font-semibold text-white">
                                <i class="fas fa-list-check mr-2"></i>Preview Invoice
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Summary -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-blue-600" x-text="preview?.total_customers || 0"></div>
                                    <div class="text-sm text-gray-600">Total Pelanggan</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-green-600" x-text="preview?.to_generate || 0"></div>
                                    <div class="text-sm text-gray-600">Akan Dibuat</div>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-yellow-600" x-text="preview?.already_invoiced || 0"></div>
                                    <div class="text-sm text-gray-600">Sudah Ada</div>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-purple-600" x-text="formatRupiah(preview?.total_amount || 0)"></div>
                                    <div class="text-sm text-gray-600">Total Tagihan</div>
                                </div>
                            </div>

                            <!-- Customer List -->
                            <div x-show="preview?.customers?.length > 0" class="max-h-64 overflow-y-auto border rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pelanggan</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Paket</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Tagihan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <template x-for="customer in preview?.customers || []" :key="customer.id">
                                            <tr>
                                                <td class="px-4 py-2 text-sm" x-text="customer.name"></td>
                                                <td class="px-4 py-2 text-sm" x-text="customer.package"></td>
                                                <td class="px-4 py-2 text-sm text-right" x-text="formatRupiah(customer.total)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Generate Button -->
                            <form method="POST" action="{{ route('admin.invoices.bulk.generate') }}" class="mt-6">
                                @csrf
                                <input type="hidden" name="billing_month" x-model="form.billing_month">
                                <input type="hidden" name="due_date" x-model="form.due_date">
                                <template x-for="packageId in form.package_ids" :key="packageId">
                                    <input type="hidden" name="package_ids[]" :value="packageId">
                                </template>
                                <input type="hidden" name="send_notification" :value="form.send_notification ? 1 : 0">

                                <button type="submit" x-show="preview?.to_generate > 0"
                                    class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-check mr-2"></i>Generate <span x-text="preview?.to_generate"></span> Invoice
                                </button>

                                <div x-show="preview?.to_generate === 0" class="text-center text-yellow-600 py-4">
                                    <i class="fas fa-info-circle mr-2"></i>Semua invoice untuk bulan ini sudah dibuat
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-pink-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-info-circle mr-2"></i>Informasi
                            </h3>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-users text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $customerCount }}</div>
                                    <div class="text-sm text-gray-500">Pelanggan aktif dengan paket</div>
                                </div>
                            </div>

                            <hr>

                            <div class="text-sm text-gray-600 space-y-2">
                                <p><i class="fas fa-check text-green-500 mr-2"></i>Invoice dibuat berdasarkan paket pelanggan</p>
                                <p><i class="fas fa-check text-green-500 mr-2"></i>Pajak dihitung otomatis dari paket</p>
                                <p><i class="fas fa-check text-green-500 mr-2"></i>Invoice duplikat akan di-skip</p>
                                <p><i class="fas fa-check text-green-500 mr-2"></i>Hutang pelanggan di-update otomatis</p>
                            </div>

                            <hr>

                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>Perhatian:</strong> Pelanggan dengan 3+ invoice belum bayar akan di-isolir otomatis.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bulkInvoice() {
    return {
        loading: false,
        preview: null,
        form: {
            billing_month: new Date().toISOString().slice(0, 7),
            due_date: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().slice(0, 10),
            package_ids: [],
            send_notification: false,
        },

        async previewInvoices() {
            this.loading = true;
            this.preview = null;

            try {
                const response = await fetch('{{ route("admin.invoices.bulk.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(this.form),
                });

                this.preview = await response.json();
            } catch (error) {
                console.error('Preview error:', error);
                alert('Gagal memuat preview');
            } finally {
                this.loading = false;
            }
        },

        formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
    }
}
</script>
@endsection
