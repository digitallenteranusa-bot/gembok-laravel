@extends('layouts.customer')

@section('title', 'Tiket Support')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tiket Support</h1>
            <p class="text-gray-600">Kelola tiket bantuan Anda</p>
        </div>
        <button onclick="document.getElementById('createTicketModal').classList.remove('hidden')" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700">
            <i class="fas fa-plus mr-2"></i>Buat Tiket
        </button>
    </div>

    <!-- Tickets List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-100">
            @forelse($tickets ?? [] as $ticket)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="text-sm font-mono text-gray-500">{{ $ticket->ticket_number }}</span>
                            <span class="px-2 py-0.5 text-xs rounded-full 
                                {{ $ticket->status == 'open' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $ticket->status == 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $ticket->status == 'resolved' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $ticket->status == 'closed' ? 'bg-gray-100 text-gray-700' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                            <span class="px-2 py-0.5 text-xs rounded-full 
                                {{ $ticket->priority == 'high' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $ticket->priority == 'medium' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $ticket->priority == 'low' ? 'bg-gray-100 text-gray-700' : '' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-800">{{ $ticket->subject }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ Str::limit($ticket->description, 100) }}</p>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        {{ $ticket->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-ticket-alt text-4xl mb-3"></i>
                <p>Belum ada tiket support</p>
            </div>
            @endforelse
        </div>
        @if(isset($tickets) && $tickets->hasPages())
        <div class="px-4 py-3 border-t">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>

<!-- Create Ticket Modal -->
<div id="createTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4">
        <h3 class="text-lg font-bold mb-4">Buat Tiket Baru</h3>
        <form action="{{ route('customer.support.submit') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subjek</label>
                    <input type="text" name="subject" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="category" required class="w-full rounded-lg border-gray-300 shadow-sm">
                            <option value="technical">Teknis</option>
                            <option value="billing">Tagihan</option>
                            <option value="installation">Instalasi</option>
                            <option value="complaint">Keluhan</option>
                            <option value="inquiry">Pertanyaan</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select name="priority" class="w-full rounded-lg border-gray-300 shadow-sm">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                    <textarea name="message" rows="4" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-cyan-500 focus:border-cyan-500" placeholder="Jelaskan masalah Anda..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">Kirim Tiket</button>
            </div>
        </form>
    </div>
</div>
@endsection
