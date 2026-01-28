@extends('layouts.app')

@section('title', 'RADIUS History - ' . $username)

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Session History: {{ $username }}</h1>
                    <p class="text-gray-600">Riwayat koneksi user</p>
                </div>
                <a href="{{ route('admin.radius.users') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Users
                </a>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stop Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Download</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Upload</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terminate Cause</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($history as $session)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $session->acctstarttime }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $session->acctstoptime ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ gmdate('H:i:s', $session->acctsessiontime ?? 0) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format(($session->acctinputoctets ?? 0) / 1048576, 2) }} MB</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format(($session->acctoutputoctets ?? 0) / 1048576, 2) }} MB</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $session->acctterminatecause ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada history</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
