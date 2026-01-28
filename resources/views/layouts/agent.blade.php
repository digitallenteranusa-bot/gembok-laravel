<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Agent Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen" x-data="{ sidebarOpen: false }">
    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed top-0 left-0 z-50 w-64 h-screen bg-gradient-to-b from-slate-900 to-slate-800 transition-transform lg:translate-x-0">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 border-b border-slate-700">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-white"></i>
                </div>
                <span class="text-xl font-bold text-white">Agent</span>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="p-4">
            <div class="bg-gradient-to-r from-emerald-600 to-green-600 rounded-lg p-4 text-white">
                <p class="text-emerald-100 text-sm">Saldo Anda</p>
                <p class="text-2xl font-bold">Rp {{ number_format(Auth::user()->balance ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-2">
            <a href="{{ route('agent.dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-slate-700 {{ request()->routeIs('agent.dashboard') ? 'bg-slate-700 text-white' : '' }}">
                <i class="fas fa-home w-5 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('agent.vouchers.sell') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-slate-700 {{ request()->routeIs('agent.vouchers*') ? 'bg-slate-700 text-white' : '' }}">
                <i class="fas fa-ticket w-5 mr-3"></i>
                <span>Jual Voucher</span>
            </a>
            <a href="{{ route('agent.topup') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-slate-700 {{ request()->routeIs('agent.topup*') ? 'bg-slate-700 text-white' : '' }}">
                <i class="fas fa-plus-circle w-5 mr-3"></i>
                <span>Top Up Saldo</span>
            </a>
            <a href="{{ route('agent.transactions') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-slate-700 {{ request()->routeIs('agent.transactions*') ? 'bg-slate-700 text-white' : '' }}">
                <i class="fas fa-history w-5 mr-3"></i>
                <span>Riwayat Transaksi</span>
            </a>
            <a href="{{ route('agent.profile') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-slate-700 {{ request()->routeIs('agent.profile*') ? 'bg-slate-700 text-white' : '' }}">
                <i class="fas fa-user-cog w-5 mr-3"></i>
                <span>Profil</span>
            </a>
        </nav>

        <!-- Logout -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
            <form action="{{ route('agent.logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center w-full px-4 py-3 text-gray-300 rounded-lg hover:bg-red-600 hover:text-white transition">
                    <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center justify-between px-4 py-3">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">{{ Auth::user()->name ?? 'Agent' }}</span>
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-store text-emerald-600"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
