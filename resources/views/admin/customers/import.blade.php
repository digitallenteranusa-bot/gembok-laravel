@extends('layouts.app')

@section('title', 'Import Pelanggan')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('admin.customers.index') }}" class="hover:text-gray-700">Customers</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span>Import</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Import Pelanggan</h1>
                <p class="text-gray-600 mt-1">Import data pelanggan dari file Excel</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Import Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600">
                            <h3 class="text-xl font-semibold text-white">
                                <i class="fas fa-file-import mr-2"></i>Upload File Excel
                            </h3>
                        </div>

                        <form action="{{ route('admin.customers.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
                            @csrf

                            @if($errors->any())
                            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-red-800">Terjadi kesalahan:</h4>
                                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-green-400 transition" id="dropzone">
                                    <div class="space-y-1 text-center">
                                        <i class="fas fa-file-excel text-5xl text-gray-400 mb-3" id="fileIcon"></i>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none">
                                                <span>Pilih file</span>
                                                <input id="file-upload" name="file" type="file" class="sr-only" accept=".xlsx,.xls" required onchange="handleFileSelect(this)">
                                            </label>
                                            <p class="pl-1">atau drag & drop file ke sini</p>
                                        </div>
                                        <p class="text-xs text-gray-500" id="fileName">Format yang didukung: .xlsx, .xls (maksimal 10MB)</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <a href="{{ route('admin.customers.template') }}" class="inline-flex items-center text-green-600 hover:text-green-800">
                                    <i class="fas fa-download mr-2"></i>Download Template Excel
                                </a>

                                <div class="flex gap-3">
                                    <a href="{{ route('admin.customers.index') }}" class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                        Batal
                                    </a>
                                    <button type="submit" class="px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-upload mr-2"></i>Import Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-cyan-600">
                            <h3 class="text-xl font-semibold text-white">
                                <i class="fas fa-info-circle mr-2"></i>Petunjuk Import
                            </h3>
                        </div>

                        <div class="p-6 space-y-4">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Format File</h4>
                                <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                    <li>Gunakan file Excel (.xlsx atau .xls)</li>
                                    <li>Ukuran maksimal 10MB</li>
                                    <li>Download template untuk format yang benar</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Kolom Wajib</h4>
                                <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                    <li><strong>name</strong> - Nama pelanggan</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Kolom Opsional</h4>
                                <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                    <li>username - Username login</li>
                                    <li>pppoe_username - Username PPPoE</li>
                                    <li>pppoe_password - Password PPPoE</li>
                                    <li>phone - Nomor telepon</li>
                                    <li>email - Email</li>
                                    <li>address - Alamat</li>
                                    <li>package_name - Nama paket</li>
                                    <li>static_ip - IP Statis</li>
                                    <li>mac_address - MAC Address</li>
                                    <li>collector_name - Nama kolektor</li>
                                    <li>status - Status (active/inactive/suspended)</li>
                                    <li>join_date - Tanggal bergabung</li>
                                </ul>
                            </div>

                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-800 mb-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Perhatian
                                </h4>
                                <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                                    <li>Username yang sudah ada akan di-skip</li>
                                    <li>Nama paket harus persis sama dengan yang ada di sistem</li>
                                    <li>Nama kolektor harus persis sama dengan yang ada di sistem</li>
                                    <li>Data yang gagal akan dilaporkan setelah import</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleFileSelect(input) {
    const fileName = document.getElementById('fileName');
    const fileIcon = document.getElementById('fileIcon');

    if (input.files && input.files[0]) {
        fileName.textContent = input.files[0].name;
        fileName.classList.add('text-green-600', 'font-medium');
        fileIcon.classList.remove('text-gray-400');
        fileIcon.classList.add('text-green-500');
    }
}

// Drag and drop support
const dropzone = document.getElementById('dropzone');
if (dropzone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.add('border-green-500', 'bg-green-50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.remove('border-green-500', 'bg-green-50');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        const fileInput = document.getElementById('file-upload');
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(fileInput);
        }
    }, false);
}
</script>
@endsection
