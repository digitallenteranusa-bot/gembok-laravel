@extends('layouts.app')

@section('title', 'Accounting Integration')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Accounting Integration</h1>
                    <p class="text-gray-600">Integrasi dengan software akuntansi</p>
                </div>
                <a href="{{ route('admin.integration.crm') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-users-cog mr-2"></i>CRM Integration
                </a>
            </div>

            @if(!$enabled)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                    <div>
                        <h3 class="text-yellow-800 font-medium">Accounting Tidak Aktif</h3>
                        <p class="text-yellow-700 text-sm mt-1">Aktifkan Accounting dengan mengatur variabel berikut di file .env:</p>
                        <pre class="mt-2 bg-yellow-100 p-2 rounded text-xs">ACCOUNTING_ENABLED=true
ACCOUNTING_PROVIDER=accurate
ACCOUNTING_API_KEY=your_api_key
ACCOUNTING_API_URL=https://api.accurate.id
ACCOUNTING_COMPANY_ID=your_company_id</pre>
                    </div>
                </div>
            </div>
            @else

            <!-- Status Card -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 bg-emerald-100 rounded-lg">
                            <i class="fas fa-calculator text-emerald-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-bold text-gray-800">{{ ucfirst($provider) }}</h3>
                            <p class="text-sm text-gray-500">Accounting Provider</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Connected</span>
                </div>
            </div>

            <!-- Sync Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-user text-blue-500 mr-2"></i>Sync Customer</h3>
                    <form action="{{ route('admin.integration.accounting.sync-customer') }}" method="POST">
                        @csrf
                        <select name="customer_id" required class="w-full rounded-lg border-gray-300 shadow-sm mb-4">
                            <option value="">-- Pilih Customer --</option>
                            @foreach(\App\Models\Customer::orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-sync mr-2"></i>Sync Customer
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-file-invoice text-green-500 mr-2"></i>Sync Invoice</h3>
                    <form action="{{ route('admin.integration.accounting.sync-invoice') }}" method="POST">
                        @csrf
                        <select name="invoice_id" required class="w-full rounded-lg border-gray-300 shadow-sm mb-4">
                            <option value="">-- Pilih Invoice --</option>
                            @foreach(\App\Models\Invoice::with('customer')->latest()->limit(50)->get() as $invoice)
                            <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - {{ $invoice->customer->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-sync mr-2"></i>Sync Invoice
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-cogs text-purple-500 mr-2"></i>Test Connection</h3>
                    <p class="text-sm text-gray-500 mb-4">Test koneksi ke accounting provider.</p>
                    <button onclick="testAccountingConnection()" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-plug mr-2"></i>Test Connection
                    </button>
                    <div id="testResult" class="mt-4 hidden"></div>
                </div>
            </div>

            <!-- Supported Providers -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-bold text-gray-800 mb-4">Supported Providers</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 border rounded-lg {{ $provider == 'accurate' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200' }}">
                        <h4 class="font-bold">Accurate Online</h4>
                        <p class="text-sm text-gray-500">Software akuntansi populer di Indonesia</p>
                    </div>
                    <div class="p-4 border rounded-lg {{ $provider == 'jurnal' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200' }}">
                        <h4 class="font-bold">Jurnal.id</h4>
                        <p class="text-sm text-gray-500">Cloud accounting by Mekari</p>
                    </div>
                    <div class="p-4 border rounded-lg {{ $provider == 'zahir' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200' }}">
                        <h4 class="font-bold">Zahir</h4>
                        <p class="text-sm text-gray-500">Software akuntansi lokal</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function testAccountingConnection() {
    fetch('{{ route("admin.integration.accounting.test") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }})
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('testResult');
            el.classList.remove('hidden');
            el.className = 'mt-4 p-3 rounded-lg ' + (data.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            el.innerHTML = '<i class="fas ' + (data.success ? 'fa-check-circle' : 'fa-times-circle') + ' mr-2"></i>' + data.message;
        });
}
</script>
@endpush
@endsection
