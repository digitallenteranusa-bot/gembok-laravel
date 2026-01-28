<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Agent::query();

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

        $agents = $query->latest()->paginate(20);

        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:agents,phone',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'address' => 'nullable|string',
        ]);

        // Generate username from email or phone
        $username = $request->email 
            ? explode('@', $request->email)[0] 
            : 'agent' . ($request->phone ?? time());
        
        // Make sure username is unique
        $baseUsername = $username;
        $counter = 1;
        while (\App\Models\Agent::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        \App\Models\Agent::create([
            'username' => $username,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'address' => $request->address,
            'status' => 'active',
            'balance' => 0,
        ]);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent created successfully!');
    }

    public function show(\App\Models\Agent $agent)
    {
        $stats = [
            'current_balance' => $agent->balance,
            'total_sales' => 0, // Will be implemented with voucher sales
            'total_commission' => 0,
        ];

        return view('admin.agents.show', compact('agent', 'stats'));
    }

    public function edit(\App\Models\Agent $agent)
    {
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, \App\Models\Agent $agent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:agents,phone,' . $agent->id,
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive,suspended',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $agent->update($validated);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent updated successfully!');
    }

    public function destroy(\App\Models\Agent $agent)
    {
        $agent->delete();

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent deleted successfully!');
    }

    public function balance(\App\Models\Agent $agent)
    {
        // Will be implemented with transaction history
        $transactions = collect([]);
        return view('admin.agents.balance', compact('agent', 'transactions'));
    }

    public function topup(Request $request, \App\Models\Agent $agent)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1000',
            'notes' => 'nullable|string',
        ]);

        // In a real app, we would create a transaction record here
        $agent->increment('balance', $validated['amount']);

        return redirect()->back()->with('success', 'Balance topped up successfully!');
    }
}
