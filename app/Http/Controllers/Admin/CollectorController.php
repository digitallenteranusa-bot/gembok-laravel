<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\CollectorReportExport;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;

class CollectorController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Collector::withCount('customers');

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
        $collector->load(['payments' => function($q) {
            $q->with(['invoice.customer'])->latest()->limit(10);
        }, 'customers']);

        // Calculate stats
        $stats = [
            'total_customers' => $collector->customers()->count(),
            'total_collected' => $collector->payments()->sum('amount'),
            'this_month' => $collector->payments()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'total_debt' => $collector->customers()->sum('total_debt'),
            'commission_earned' => $collector->payments()->sum('commission'),
        ];

        return view('admin.collectors.show', compact('collector', 'stats'));
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

    public function payments(\App\Models\Collector $collector, Request $request)
    {
        $query = Payment::where('collector_id', $collector->id)
            ->with(['invoice.customer']);

        if ($request->filled('start_date')) {
            $query->where('paid_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('paid_at', '<=', $request->end_date . ' 23:59:59');
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(20);

        return view('admin.collectors.payments', compact('collector', 'payments'));
    }

    /**
     * Show collector report
     */
    public function report(\App\Models\Collector $collector, Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Get customers assigned to this collector
        $customers = Customer::where('collector_id', $collector->id)
            ->with(['package', 'invoices' => function($q) {
                $q->where('status', 'unpaid');
            }])
            ->orderBy('name')
            ->get();

        // Get payments
        $paymentsQuery = Payment::where('collector_id', $collector->id)
            ->with(['invoice.customer']);
        if ($startDate) {
            $paymentsQuery->where('paid_at', '>=', $startDate);
        }
        if ($endDate) {
            $paymentsQuery->where('paid_at', '<=', $endDate . ' 23:59:59');
        }
        $payments = $paymentsQuery->orderBy('paid_at', 'desc')->get();

        // Calculate totals
        $stats = [
            'total_customers' => $customers->count(),
            'total_debt' => $customers->sum('total_debt'),
            'total_collection' => $payments->sum('amount'),
            'total_transactions' => $payments->count(),
        ];

        return view('admin.collectors.report', compact('collector', 'customers', 'payments', 'stats', 'startDate', 'endDate'));
    }

    /**
     * Export collector report to Excel
     */
    public function exportReport(\App\Models\Collector $collector, Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = new CollectorReportExport($collector, $startDate, $endDate);
        return $export->download();
    }
}
