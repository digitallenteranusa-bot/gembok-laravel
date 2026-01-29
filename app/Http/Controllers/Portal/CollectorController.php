<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CollectorController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $collector = Collector::where('username', $request->username)->first();

        if ($collector && Hash::check($request->password, $collector->password)) {
            Auth::loginUsingId($collector->user_id ?? $collector->id);
            session(['collector_id' => $collector->id]);
            return redirect()->route('collector.dashboard');
        }

        return back()->with('error', 'Username atau password salah');
    }

    public function logout()
    {
        session()->forget('collector_id');
        Auth::logout();
        return redirect()->route('collector.login');
    }

    public function dashboard()
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        // Get customer IDs assigned to this collector
        $customerIds = \App\Models\Customer::where('collector_id', $collector->id)->pluck('id');

        // Stats only for assigned customers
        $todayTarget = Invoice::where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds)
            ->whereDate('due_date', '<=', today())
            ->count();

        $todayCollected = Payment::where('collector_id', $collector->id)
            ->whereDate('created_at', today())
            ->sum('amount');

        $monthCommission = Payment::where('collector_id', $collector->id)
            ->whereMonth('created_at', now()->month)
            ->sum('commission');

        $unpaidCount = Invoice::where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds)
            ->count();

        // Only show invoices from assigned customers
        $pendingInvoices = Invoice::with('customer')
            ->where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds)
            ->orderBy('due_date')
            ->take(10)
            ->get();

        $todayCollections = Payment::with('invoice.customer')
            ->where('collector_id', $collector->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        // Additional stats
        $totalCustomers = $customerIds->count();
        $totalDebt = Invoice::where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds)
            ->sum('total');

        return view('collector.dashboard', compact(
            'collector', 'todayTarget', 'todayCollected', 'monthCommission',
            'unpaidCount', 'pendingInvoices', 'todayCollections',
            'totalCustomers', 'totalDebt'
        ));
    }

    public function invoices(Request $request)
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        // Get customer IDs assigned to this collector
        $customerIds = \App\Models\Customer::where('collector_id', $collector->id)->pluck('id');

        $query = Invoice::with('customer')
            ->where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds); // Only show assigned customers

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        if ($request->get('overdue') == '1') {
            $query->where('due_date', '<', today());
        }

        $invoices = $query->orderBy('due_date')->paginate(20);

        // Stats for this collector
        $totalDebt = Invoice::where('status', 'unpaid')
            ->whereIn('customer_id', $customerIds)
            ->sum('total');

        return view('collector.invoices', compact('collector', 'invoices', 'totalDebt'));
    }

    public function collect(Invoice $invoice = null)
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        if ($invoice) {
            $invoice->load('customer');

            // Validate: collector can only collect from assigned customers
            if ($invoice->customer->collector_id !== $collector->id) {
                return redirect()->route('collector.invoices')
                    ->with('error', 'Anda tidak memiliki akses ke pelanggan ini');
            }
        }

        return view('collector.collect', compact('collector', 'invoice'));
    }

    public function processPayment(Request $request, Invoice $invoice)
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        // Validate: collector can only process payments for assigned customers
        $invoice->load('customer');
        if ($invoice->customer->collector_id !== $collector->id) {
            return redirect()->route('collector.invoices')
                ->with('error', 'Anda tidak memiliki akses ke pelanggan ini');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,transfer',
        ]);

        // Calculate commission
        $commissionRate = $collector->commission_rate ?? 2; // 2% default
        $commission = ($request->amount * $commissionRate) / 100;

        // Create payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'collector_id' => $collector->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'commission' => $commission,
            'notes' => $request->notes,
        ]);

        // Update invoice status if fully paid
        if ($request->amount >= $invoice->total) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $request->payment_method,
            ]);

            // Activate customer if suspended
            if ($invoice->customer->status === 'suspended') {
                $invoice->customer->update(['status' => 'active']);
            }

            // Send confirmation via WhatsApp
            try {
                $whatsapp = new WhatsAppService();
                $whatsapp->sendPaymentConfirmation($invoice);
            } catch (\Exception $e) {
                // Log error
            }
        }

        return redirect()->route('collector.dashboard')
            ->with('success', 'Pembayaran berhasil dicatat!');
    }

    public function history(Request $request)
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        $query = Payment::with('invoice.customer')
            ->where('collector_id', $collector->id);

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        $totalCollected = Payment::where('collector_id', $collector->id)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $totalCommission = Payment::where('collector_id', $collector->id)
            ->whereMonth('created_at', now()->month)
            ->sum('commission');

        return view('collector.history', compact('collector', 'payments', 'totalCollected', 'totalCommission'));
    }

    public function profile()
    {
        $collector = Collector::find(session('collector_id'));

        if (!$collector) {
            return redirect()->route('collector.login')->with('error', 'Sesi tidak valid');
        }

        // Get collector stats
        $customerIds = \App\Models\Customer::where('collector_id', $collector->id)->pluck('id');

        $stats = [
            'total_customers' => $customerIds->count(),
            'total_collected' => Payment::where('collector_id', $collector->id)->sum('amount'),
            'total_commission' => Payment::where('collector_id', $collector->id)->sum('commission'),
            'this_month_collected' => Payment::where('collector_id', $collector->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'this_month_commission' => Payment::where('collector_id', $collector->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission'),
        ];

        return view('collector.profile', compact('collector', 'stats'));
    }
}
