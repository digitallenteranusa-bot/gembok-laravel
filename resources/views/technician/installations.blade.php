@extends('layouts.technician')

@section('title', 'Instalasi')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Daftar Instalasi</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-100">
            @forelse($installations ?? [] as $customer)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-plug text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $customer->name }}</p>
                            <p class="text-sm text-gray-500">{{ $customer->address }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-box mr-1"></i>{{ $customer->package->name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">
                            Menunggu Instalasi
                        </span>
                        <div class="mt-2 space-x-2">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" 
                               class="text-green-600 hover:text-green-700 text-sm">
                                <i class="fab fa-whatsapp mr-1"></i> WA
                            </a>
                            <a href="https://maps.google.com/?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                               class="text-blue-600 hover:text-blue-700 text-sm">
                                <i class="fas fa-map-marker-alt mr-1"></i> Map
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                <p>Tidak ada instalasi pending</p>
            </div>
            @endforelse
        </div>
        
        @if(isset($installations) && $installations->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $installations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
