<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollectorController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Collector::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $collectors = $query->latest()->paginate(20);

        return view('admin.collectors.index', compact('collectors'));
    }

    public function create()
    {
        return view('admin.collectors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:collectors,phone',
            'email' => 'nullable|email|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'password' => 'nullable|string|min:6',
        ]);

        $validated['status'] = 'active';
        $validated['commission_rate'] = $validated['commission_rate'] ?? 10.0;
        
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        \App\Models\Collector::create($validated);

        return redirect()->route('admin.collectors.index')
            ->with('success', 'Collector created successfully!');
    }

    public function show(\App\Models\Collector $collector)
    {
        return view('admin.collectors.show', compact('collector'));
    }

    public function edit(\App\Models\Collector $collector)
    {
        return view('admin.collectors.edit', compact('collector'));
    }

    public function update(Request $request, \App\Models\Collector $collector)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:collectors,phone,' . $collector->id,
            'email' => 'nullable|email|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $collector->update($validated);

        return redirect()->route('admin.collectors.index')
            ->with('success', 'Collector updated successfully!');
    }

    public function destroy(\App\Models\Collector $collector)
    {
        $collector->delete();

        return redirect()->route('admin.collectors.index')
            ->with('success', 'Collector deleted successfully!');
    }

    public function payments(\App\Models\Collector $collector)
    {
        // Will be implemented with payment records
        $payments = collect([]);
        return view('admin.collectors.payments', compact('collector', 'payments'));
    }
}
