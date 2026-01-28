@extends('layouts.app')

@section('title', 'RADIUS Groups')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    @include('admin.partials.sidebar')

    <div class="lg:pl-64">
        @include('admin.partials.topbar')

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">RADIUS Groups</h1>
                    <p class="text-gray-600">Bandwidth Profiles untuk RADIUS</p>
                </div>
                <button onclick="document.getElementById('addGroupModal').classList.remove('hidden')" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                    <i class="fas fa-plus mr-2"></i>Add Group
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($groups as $groupname => $attributes)
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">{{ $groupname }}</h3>
                    <div class="space-y-2">
                        @foreach($attributes as $attr)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ $attr->attribute }}</span>
                            <span class="font-mono text-gray-800">{{ $attr->value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="col-span-3 bg-gray-50 rounded-xl p-8 text-center text-gray-500">
                    Belum ada group/profile
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Group Modal -->
<div id="addGroupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Add Bandwidth Profile</h3>
        <form action="{{ route('admin.radius.groups.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Group Name</label>
                    <input type="text" name="groupname" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="e.g. 10Mbps">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Download Limit</label>
                    <input type="text" name="download_limit" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="e.g. 10M">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload Limit</label>
                    <input type="text" name="upload_limit" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="e.g. 5M">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addGroupModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
