@extends('layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.orders.index') }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $order->order_number }}</h1>
                        <p class="text-gray-600">{{ $order->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @php $badge = $order->status_badge; @endphp
                    <span class="px-4 py-2 rounded-lg bg-{{ $badge['color'] }}-100 text-{{ $badge['color'] }}-800 font-medium">{{ $badge['label'] }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-user mr-2 text-cyan-600"></i>Data Pelanggan</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nama</p>
                                <p class="font-medium">{{ $order->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Telepon</p>
                                <p class="font-medium">
                                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', $order->customer_phone) }}" target="_blank" class="text-green-600 hover:underline">
                                        <i class="fab fa-whatsapp mr-1"></i>{{ $order->customer_phone }}
                                    </a>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium">{{ $order->customer_email ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">NIK</p>
                                <p class="font-medium">{{ $order->customer_nik ?? '-' }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-500">Alamat</p>
                                <p class="font-medium">{{ $order->customer_address }}</p>
                                @if($order->latitude && $order->longitude)
                                <a href="https://maps.google.com/?q={{ $order->latitude }},{{ $order->longitude }}" target="_blank" class="text-sm text-cyan-600 hover:underline">
                                    <i class="fas fa-map-marker-alt mr-1"></i>Lihat di Maps
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Package Info -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-box mr-2 text-cyan-600"></i>Detail Paket</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Paket</p>
                                <p class="font-medium">{{ $order->package->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Kecepatan</p>
                                <p class="font-medium">{{ $order->package->speed }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Tipe Koneksi</p>
                                <p class="font-medium">{{ strtoupper($order->connection_type) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Harga Paket</p>
                                <p class="font-medium">Rp {{ number_format($order->package_price, 0, ',', '.') }}/bulan</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-credit-card mr-2 text-cyan-600"></i>Pembayaran</h3>
                        @php $payBadge = $order->payment_status_badge; @endphp
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <span class="px-2 py-1 text-xs rounded-full bg-{{ $payBadge['color'] }}-100 text-{{ $payBadge['color'] }}-800">{{ $payBadge['label'] }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Metode</p>
                                <p class="font-medium">{{ ucfirst($order->payment_method) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Biaya Paket</p>
                                <p class="font-medium">Rp {{ number_format($order->package_price, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Biaya Pemasangan</p>
                                <p class="font-medium">Rp {{ number_format($order->installation_fee, 0, ',', '.') }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-500">Total</p>
                                <p class="text-2xl font-bold text-cyan-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </div>
                            @if($order->paid_at)
                            <div>
                                <p class="text-sm text-gray-500">Tanggal Bayar</p>
                                <p class="font-medium">{{ $order->paid_at->format('d M Y H:i') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($order->customer_notes)
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-sticky-note mr-2 text-cyan-600"></i>Catatan Pelanggan</h3>
                        <p class="text-gray-700">{{ $order->customer_notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Actions Sidebar -->
                <div class="space-y-6">
                    <!-- Update Status -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Update Status</h3>
                        <form id="statusForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status Pesanan</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                                    <option value="scheduled" {{ $order->status == 'scheduled' ? 'selected' : '' }}>Dijadwalkan</option>
                                    <option value="installing" {{ $order->status == 'installing' ? 'selected' : '' }}>Pemasangan</option>
                                    <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pemasangan</label>
                                <input type="date" name="installation_date" value="{{ $order->installation_date?->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu</label>
                                <select name="installation_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Pilih Waktu</option>
                                    <option value="08:00-10:00" {{ $order->installation_time == '08:00-10:00' ? 'selected' : '' }}>08:00 - 10:00</option>
                                    <option value="10:00-12:00" {{ $order->installation_time == '10:00-12:00' ? 'selected' : '' }}>10:00 - 12:00</option>
                                    <option value="13:00-15:00" {{ $order->installation_time == '13:00-15:00' ? 'selected' : '' }}>13:00 - 15:00</option>
                                    <option value="15:00-17:00" {{ $order->installation_time == '15:00-17:00' ? 'selected' : '' }}>15:00 - 17:00</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teknisi</label>
                                <select name="technician_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Pilih Teknisi</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $order->technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Admin</label>
                                <textarea name="admin_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ $order->admin_notes }}</textarea>
                            </div>
                            <button type="submit" class="w-full bg-cyan-600 text-white py-2 rounded-lg hover:bg-cyan-700">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                        </form>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Aksi Cepat</h3>
                        <div class="space-y-2">
                            @if($order->payment_status === 'pending')
                            <button onclick="confirmPayment()" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran
                            </button>
                            @endif
                            
                            @if($order->status === 'installing' && $order->payment_status === 'paid')
                            <button onclick="completeOrder()" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-user-plus mr-2"></i>Selesai & Buat Pelanggan
                            </button>
                            @endif

                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', $order->customer_phone) }}" target="_blank" class="block w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 text-center">
                                <i class="fab fa-whatsapp mr-2"></i>Hubungi via WA
                            </a>
                        </div>
                    </div>

                    @if($order->customer_id)
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-green-800 font-medium"><i class="fas fa-check-circle mr-2"></i>Sudah jadi pelanggan</p>
                        <a href="{{ route('admin.customers.show', $order->customer_id) }}" class="text-sm text-green-600 hover:underline">Lihat data pelanggan →</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    Swal.fire({
        title: 'Update Status?',
        text: 'Apakah Anda yakin ingin mengupdate status pesanan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0891b2',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Update!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading('Menyimpan...');
            fetch('{{ route("admin.orders.update-status", $order) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(r => r.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    showError(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(() => {
                Swal.close();
                showError('Terjadi kesalahan jaringan');
            });
        }
    });
});

function confirmPayment() {
    Swal.fire({
        title: 'Konfirmasi Pembayaran?',
        text: 'Apakah Anda yakin pembayaran sudah diterima?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Konfirmasi!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading('Memproses...');
            fetch('{{ route("admin.orders.confirm-payment", $order) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pembayaran Dikonfirmasi!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => location.reload());
                } else {
                    showError(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(() => {
                Swal.close();
                showError('Terjadi kesalahan jaringan');
            });
        }
    });
}

function completeOrder() {
    Swal.fire({
        title: 'Selesaikan Pesanan?',
        html: 'Pesanan akan diselesaikan dan <strong>akun pelanggan baru</strong> akan dibuat secara otomatis.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Selesaikan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading('Membuat akun pelanggan...');
            fetch('{{ route("admin.orders.complete", $order) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pesanan Selesai!',
                        text: data.message,
                        showConfirmButton: true,
                        confirmButtonColor: '#0891b2'
                    }).then(() => location.reload());
                } else {
                    showError(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(() => {
                Swal.close();
                showError('Terjadi kesalahan jaringan');
            });
        }
    });
}
</script>
@endpush
@endsection
