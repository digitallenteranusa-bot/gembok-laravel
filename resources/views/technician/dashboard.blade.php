@extends('layouts.technician')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-orange-600 to-red-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Selamat Datang, {{ $technician->name ?? 'Teknisi' }}!</h1>
                <p class="text-orange-100 mt-1">Role: {{ ucfirst($technician->role ?? 'Technician') }} • Area: {{ $technician->area ?? 'Semua Area' }}</p>
            </div>
            <div class="hidden md:block">
                <i class="fas fa-tools text-6xl text-white/20"></i>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Tugas Hari Ini</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $todayTasks ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Selesai</p>
                    <p class="text-2xl font-bold text-green-600">{{ $completedTasks ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $pendingTasks ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Bulan Ini</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $monthTasks ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('technician.tasks') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-tasks text-orange-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Tugas Saya</p>
        </a>
        <a href="{{ route('technician.installations') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-plug text-blue-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Instalasi</p>
        </a>
        <a href="{{ route('technician.repairs') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-wrench text-red-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Perbaikan</p>
        </a>
        <a href="{{ route('technician.profile') }}" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:shadow-md transition text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-cog text-gray-600 text-xl"></i>
            </div>
            <p class="font-medium text-gray-800">Profil</p>
        </a>
    </div>

    <!-- Today's Tasks -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Tugas Hari Ini</h2>
            <a href="{{ route('technician.tasks') }}" class="text-orange-600 hover:text-orange-700 text-sm">Lihat Semua</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($tasks ?? [] as $task)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 {{ $task->type == 'installation' ? 'bg-blue-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                        <i class="fas {{ $task->type == 'installation' ? 'fa-plug text-blue-600' : 'fa-wrench text-red-600' }}"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $task->customer->name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-500">{{ $task->customer->address ?? '' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex px-2 py-1 text-xs rounded-full 
                        {{ $task->status == 'completed' ? 'bg-green-100 text-green-700' : 
                           ($task->status == 'in_progress' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ ucfirst($task->status) }}
                    </span>
                    <p class="text-sm text-gray-500 mt-1">{{ $task->scheduled_at ? $task->scheduled_at->format('H:i') : '-' }}</p>
                </div>
                <a href="{{ route('technician.tasks.show', $task->id) }}" class="ml-4 bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">
                    <i class="fas fa-eye mr-1"></i> Detail
                </a>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                <p>Tidak ada tugas hari ini</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Network Map Quick Access -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Peta Jaringan</h2>
            <a href="{{ route('technician.map') }}" class="text-orange-600 hover:text-orange-700 text-sm">Buka Peta</a>
        </div>
        <div class="bg-gray-100 rounded-lg h-48 flex items-center justify-center">
            <div class="text-center text-gray-500">
                <i class="fas fa-map-marked-alt text-4xl mb-2"></i>
                <p>Klik untuk melihat peta ODP & pelanggan</p>
            </div>
        </div>
    </div>
</div>
@endsection
