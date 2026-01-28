@extends('layouts.agent')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-emerald-600 to-green-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Selamat Datang, {{ $agent->name ?? 'Agent' }}!</h1>
                <p class="text-emerald-100 mt-1">Saldo: Rp {{ number_format($agent->balance ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="hidden md:block">
                <i class="fas fa-store text-6xl text-white/20"></i>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Saldo</p>
                    <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($agent->balance ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-wallet text-emerald-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Penjualan Hari Ini</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $todaySales ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Penjualan Bulan Ini</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $monthSales ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Komisi Bulan Ini</p>
                    <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($monthCommission ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-coins text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('agent.vouchers.sell') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-ticket text-emerald-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Jual Voucher</p>
        </a>
        <a href="{{ route('agent.topup') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Top Up Saldo</p>
        </a>
        <a href="{{ route('agent.transactions') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-history text-purple-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Riwayat</p>
        </a>
        <a href="{{ route('agent.profile') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-cog text-gray-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Profil</p>
        </a>
    </div>

    <!-- Voucher Packages -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Paket Voucher Tersedia</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
            @forelse($voucherPackages ?? [] as $package)
            <div class="border border-gray-200 rounded-lg p-4 hover:border-emerald-500 transition">
                <h3 class="font-semibold text-gray-800">{{ $package->name }}</h3>
                <p class="text-sm text-gray-500">{{ $package->duration }} Hari</p>
                <div class="mt-3 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-400">Harga Agent</p>
                        <p class="font-bold text-emerald-600">Rp {{ number_format($package->agent_price, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Harga Jual</p>
                        <p class="font-bold text-gray-800">Rp {{ number_format($package->customer_price, 0, ',', '.') }}</p>
                    </div>
                </div>
                <a href="{{ route('agent.vouchers.sell', ['package' => $package->id]) }}" 
                   class="mt-3 block w-full text-center bg-emerald-500 text-white py-2 rounded-lg hover:bg-emerald-600 transition">
                    Jual
                </a>
            </div>
            @empty
            <div class="col-span-3 text-center py-8 text-gray-500">
                <i class="fas fa-ticket text-4xl mb-2"></i>
                <p>Belum ada paket voucher</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Transaksi Terbaru</h2>
            <a href="{{ route('agent.transactions') }}" class="text-emerald-600 hover:text-emerald-700 text-sm">Lihat Semua</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentTransactions ?? [] as $transaction)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 {{ $transaction->type == 'sale' ? 'bg-green-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                        <i class="fas {{ $transaction->type == 'sale' ? 'fa-shopping-cart text-green-600' : 'fa-plus text-blue-600' }}"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $transaction->description }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold {{ $transaction->type == 'sale' ? 'text-green-600' : 'text-blue-600' }}">
                        {{ $transaction->type == 'sale' ? '+' : '' }}Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>Belum ada transaksi</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
