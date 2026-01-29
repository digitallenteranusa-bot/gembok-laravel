@extends('layouts.app')

@section('title', 'Role & Permission Settings')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('admin.settings') }}" class="hover:text-blue-600">Settings</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900">Roles & Permissions</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Pengaturan Role & Permission</h1>
                <p class="text-gray-600 mt-1">Atur hak akses untuk setiap role dalam sistem</p>
            </div>

            <!-- Success/Error Message -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.settings.roles.update') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <!-- Collector Permissions -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-hand-holding-dollar mr-3"></i>Collector
                            </h2>
                            <p class="text-blue-100 text-sm mt-1">Pengaturan akses untuk collector/penagih</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_assigned_customers"
                                        {{ in_array('view_assigned_customers', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Pelanggan Sendiri</span>
                                        <p class="text-xs text-gray-500">Hanya pelanggan yang di-assign</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_all_customers"
                                        {{ in_array('view_all_customers', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Semua Pelanggan</span>
                                        <p class="text-xs text-gray-500">Akses semua data pelanggan</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="collect_payment"
                                        {{ in_array('collect_payment', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Terima Pembayaran</span>
                                        <p class="text-xs text-gray-500">Catat pembayaran dari pelanggan</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_invoice_detail"
                                        {{ in_array('view_invoice_detail', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Detail Invoice</span>
                                        <p class="text-xs text-gray-500">Akses detail invoice pelanggan</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_payment_history"
                                        {{ in_array('view_payment_history', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Riwayat Pembayaran</span>
                                        <p class="text-xs text-gray-500">Akses history pembayaran</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_commission"
                                        {{ in_array('view_commission', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Komisi</span>
                                        <p class="text-xs text-gray-500">Akses laporan komisi</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="export_report"
                                        {{ in_array('export_report', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Export Laporan</span>
                                        <p class="text-xs text-gray-500">Download laporan Excel</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_customer_address"
                                        {{ in_array('view_customer_address', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Alamat</span>
                                        <p class="text-xs text-gray-500">Akses alamat pelanggan</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="collector_permissions[]" value="view_customer_phone"
                                        {{ in_array('view_customer_phone', $permissions['collector'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat No. Telepon</span>
                                        <p class="text-xs text-gray-500">Akses nomor telepon pelanggan</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Technician Permissions -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-tools mr-3"></i>Teknisi
                            </h2>
                            <p class="text-orange-100 text-sm mt-1">Pengaturan akses untuk teknisi lapangan</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="view_assigned_tasks"
                                        {{ in_array('view_assigned_tasks', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Tugas Sendiri</span>
                                        <p class="text-xs text-gray-500">Hanya tugas yang di-assign</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="view_all_tasks"
                                        {{ in_array('view_all_tasks', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Semua Tugas</span>
                                        <p class="text-xs text-gray-500">Akses semua tugas teknisi</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="update_task_status"
                                        {{ in_array('update_task_status', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Update Status Tugas</span>
                                        <p class="text-xs text-gray-500">Ubah status tugas</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="view_customer_info"
                                        {{ in_array('view_customer_info', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Info Pelanggan</span>
                                        <p class="text-xs text-gray-500">Akses data pelanggan</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="view_network_info"
                                        {{ in_array('view_network_info', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Info Jaringan</span>
                                        <p class="text-xs text-gray-500">Akses data ODP, ONU, dll</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="view_map"
                                        {{ in_array('view_map', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Akses Peta</span>
                                        <p class="text-xs text-gray-500">Lihat lokasi di peta</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="technician_permissions[]" value="upload_photo"
                                        {{ in_array('upload_photo', $permissions['technician'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Upload Foto</span>
                                        <p class="text-xs text-gray-500">Upload dokumentasi</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Agent Permissions -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-store mr-3"></i>Agent
                            </h2>
                            <p class="text-purple-100 text-sm mt-1">Pengaturan akses untuk agent penjual voucher</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="sell_voucher"
                                        {{ in_array('sell_voucher', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Jual Voucher</span>
                                        <p class="text-xs text-gray-500">Akses penjualan voucher</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="view_balance"
                                        {{ in_array('view_balance', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Saldo</span>
                                        <p class="text-xs text-gray-500">Akses saldo deposit</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="request_topup"
                                        {{ in_array('request_topup', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Request Topup</span>
                                        <p class="text-xs text-gray-500">Ajukan topup saldo</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="view_transactions"
                                        {{ in_array('view_transactions', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Transaksi</span>
                                        <p class="text-xs text-gray-500">Akses riwayat transaksi</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="view_voucher_stock"
                                        {{ in_array('view_voucher_stock', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Stok Voucher</span>
                                        <p class="text-xs text-gray-500">Akses stok voucher tersedia</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="agent_permissions[]" value="print_voucher"
                                        {{ in_array('print_voucher', $permissions['agent'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Print Voucher</span>
                                        <p class="text-xs text-gray-500">Cetak voucher</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Portal Permissions -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-user mr-3"></i>Customer Portal
                            </h2>
                            <p class="text-green-100 text-sm mt-1">Pengaturan akses untuk portal pelanggan</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="view_invoices"
                                        {{ in_array('view_invoices', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Invoice</span>
                                        <p class="text-xs text-gray-500">Akses daftar invoice</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="pay_online"
                                        {{ in_array('pay_online', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Bayar Online</span>
                                        <p class="text-xs text-gray-500">Pembayaran via payment gateway</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="view_usage"
                                        {{ in_array('view_usage', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Lihat Pemakaian</span>
                                        <p class="text-xs text-gray-500">Statistik penggunaan internet</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="submit_ticket"
                                        {{ in_array('submit_ticket', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Buat Tiket</span>
                                        <p class="text-xs text-gray-500">Ajukan tiket support</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="update_profile"
                                        {{ in_array('update_profile', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Update Profil</span>
                                        <p class="text-xs text-gray-500">Ubah data profil</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                    <input type="checkbox" name="customer_permissions[]" value="download_invoice"
                                        {{ in_array('download_invoice', $permissions['customer'] ?? []) ? 'checked' : '' }}
                                        class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Download Invoice</span>
                                        <p class="text-xs text-gray-500">Unduh invoice PDF</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex justify-end space-x-4">
                    <a href="{{ route('admin.settings') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                    <button type="submit" class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white px-8 py-3 rounded-lg hover:from-blue-600 hover:to-cyan-700 transition transform hover:scale-105 shadow-lg font-bold">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
