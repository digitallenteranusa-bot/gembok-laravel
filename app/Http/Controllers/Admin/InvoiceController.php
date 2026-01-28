<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\InvoicePaid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentHistory;
use App\Models\InstallmentPlan;
use App\Services\WhatsAppService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected $whatsapp;
    protected $paymentGateway;

    public function __construct(WhatsAppService $whatsapp, PaymentGatewayService $paymentGateway)
    {
        $this->whatsapp = $whatsapp;
        $this->paymentGateway = $paymentGateway;
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'package']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by type
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        $invoices = $query->latest()->paginate(20);
        $customers = Customer::orderBy('name')->get();

        $stats = [
            'total' => Invoice::count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'unpaid' => Invoice::where('status', 'unpaid')->count(),
            'overdue' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('amount'),
            'pending_revenue' => Invoice::where('status', 'unpaid')->sum('amount'),
            'total_debt' => Invoice::where('status', 'unpaid')->sum(DB::raw('amount + COALESCE(tax_amount, 0) - COALESCE(paid_amount, 0)')),
        ];

        return view('admin.invoices.index', compact('invoices', 'customers', 'stats'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.invoices.create', compact('customers', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'package_id' => 'nullable|exists:packages,id',
            'amount' => 'required|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'invoice_type' => 'required|in:monthly,installation,voucher,other',
        ]);

        // Generate invoice number
        $lastInvoice = \App\Models\Invoice::latest()->first();
        $number = $lastInvoice ? (int)substr($lastInvoice->invoice_number, 4) + 1 : 1;
        $validated['invoice_number'] = 'INV-' . str_pad($number, 6, '0', STR_PAD_LEFT);
        $validated['status'] = 'unpaid';
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;

        \App\Models\Invoice::create($validated);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice created successfully!');
    }

    public function show(\App\Models\Invoice $invoice)
    {
        $invoice->load(['customer', 'package']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(\App\Models\Invoice $invoice)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.invoices.edit', compact('invoice', 'customers', 'packages'));
    }

    public function update(Request $request, \App\Models\Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'package_id' => 'nullable|exists:packages,id',
            'amount' => 'required|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'invoice_type' => 'required|in:monthly,installation,voucher,other',
        ]);

        $invoice->update($validated);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice updated successfully!');
    }

    public function destroy(\App\Models\Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Cannot delete paid invoice!');
        }

        $invoice->delete();

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    public function pay(Request $request, \App\Models\Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Invoice already paid!');
        }

        $invoice->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $request->input('payment_method', 'cash'),
            'collected_by' => auth()->id(),
        ]);

        // Fire event for automatic activation
        event(new InvoicePaid($invoice));

        return redirect()->back()
            ->with('success', 'Invoice marked as paid!');
    }

    /**
     * Send invoice notification via WhatsApp
     */
    public function sendNotification(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }

        $result = $this->whatsapp->sendInvoiceNotification($customer, $invoice);

        return response()->json($result);
    }

    /**
     * Create payment link
     */
    public function createPaymentLink(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $result = $this->paymentGateway->createPayment($invoice, $customer);

        if ($result['success']) {
            $invoice->update([
                'payment_gateway' => $result['gateway'],
                'payment_order_id' => $result['order_id'],
            ]);
        }

        return response()->json($result);
    }

    /**
     * Send payment link via WhatsApp
     */
    public function sendPaymentLink(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }

        // Create payment link first
        $paymentResult = $this->paymentGateway->createPayment($invoice, $customer);

        if (!$paymentResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment link'
            ], 500);
        }

        // Send via WhatsApp
        $message = "Halo *{$customer->name}*,\n\n";
        $message .= "Berikut link pembayaran untuk tagihan Anda:\n\n";
        $message .= "📋 *Invoice:* {$invoice->invoice_number}\n";
        $message .= "💰 *Total:* Rp " . number_format($invoice->amount, 0, ',', '.') . "\n\n";
        $message .= "🔗 *Link Pembayaran:*\n{$paymentResult['payment_url']}\n\n";
        $message .= "Link ini berlaku selama 24 jam.\n\n";
        $message .= "Terima kasih,\n";
        $message .= "*" . companyName() . "*";

        $waResult = $this->whatsapp->send($customer->phone, $message);

        return response()->json([
            'success' => $waResult['success'],
            'message' => $waResult['success'] ? 'Payment link sent via WhatsApp' : 'Failed to send WhatsApp',
            'payment_url' => $paymentResult['payment_url']
        ]);
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'package']);
        $company = [
            'name' => \App\Models\AppSetting::where('key', 'company_name')->value('value') ?? 'GEMBOK LARA',
            'phone' => \App\Models\AppSetting::where('key', 'company_phone')->value('value') ?? '-',
            'email' => \App\Models\AppSetting::where('key', 'company_email')->value('value') ?? '-',
            'address' => \App\Models\AppSetting::where('key', 'company_address')->value('value') ?? '-',
        ];

        return view('admin.invoices.print', compact('invoice', 'company'));
    }

    // ==========================================
    // BULK INVOICE GENERATION
    // ==========================================

    /**
     * Show bulk generate form
     */
    public function bulkGenerateForm()
    {
        $packages = Package::where('is_active', true)->get();
        $customerCount = Customer::where('status', 'active')->whereNotNull('package_id')->count();

        return view('admin.invoices.bulk-generate', compact('packages', 'customerCount'));
    }

    /**
     * Preview bulk generation
     */
    public function bulkGeneratePreview(Request $request)
    {
        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'due_date' => 'required|date',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
        ]);

        $query = Customer::with('package')
            ->where('status', 'active')
            ->whereNotNull('package_id');

        // Filter by packages if specified
        if ($request->filled('package_ids')) {
            $query->whereIn('package_id', $request->package_ids);
        }

        $customers = $query->get();

        // Check existing invoices for this month
        $billingMonth = $request->billing_month;
        $existingInvoices = Invoice::whereMonth('created_at', substr($billingMonth, 5, 2))
            ->whereYear('created_at', substr($billingMonth, 0, 4))
            ->where('invoice_type', 'monthly')
            ->pluck('customer_id')
            ->toArray();

        $preview = [
            'total_customers' => $customers->count(),
            'already_invoiced' => 0,
            'to_generate' => 0,
            'total_amount' => 0,
            'customers' => [],
        ];

        foreach ($customers as $customer) {
            $hasInvoice = in_array($customer->id, $existingInvoices);

            if ($hasInvoice) {
                $preview['already_invoiced']++;
            } else {
                $preview['to_generate']++;
                $amount = $customer->package->price ?? 0;
                $taxAmount = $customer->package->tax_rate ? round($amount * $customer->package->tax_rate / 100) : 0;
                $preview['total_amount'] += $amount + $taxAmount;

                $preview['customers'][] = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'package' => $customer->package->name ?? '-',
                    'amount' => $amount,
                    'tax_amount' => $taxAmount,
                    'total' => $amount + $taxAmount,
                ];
            }
        }

        return response()->json($preview);
    }

    /**
     * Process bulk invoice generation
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'due_date' => 'required|date',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
            'send_notification' => 'boolean',
        ]);

        $billingMonth = $request->billing_month;
        $dueDate = $request->due_date;
        $sendNotification = $request->boolean('send_notification', false);

        $query = Customer::with('package')
            ->where('status', 'active')
            ->whereNotNull('package_id');

        if ($request->filled('package_ids')) {
            $query->whereIn('package_id', $request->package_ids);
        }

        $customers = $query->get();

        // Get existing invoices for this month
        $existingInvoices = Invoice::whereMonth('created_at', substr($billingMonth, 5, 2))
            ->whereYear('created_at', substr($billingMonth, 0, 4))
            ->where('invoice_type', 'monthly')
            ->pluck('customer_id')
            ->toArray();

        $generated = 0;
        $skipped = 0;
        $errors = 0;
        $notifications = 0;

        DB::beginTransaction();
        try {
            foreach ($customers as $customer) {
                // Skip if already has invoice this month
                if (in_array($customer->id, $existingInvoices)) {
                    $skipped++;
                    continue;
                }

                try {
                    $amount = $customer->package->price ?? 0;
                    $taxAmount = $customer->package->tax_rate ? round($amount * $customer->package->tax_rate / 100) : 0;

                    $invoice = Invoice::create([
                        'customer_id' => $customer->id,
                        'package_id' => $customer->package_id,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'remaining_amount' => $amount + $taxAmount,
                        'description' => "Tagihan Internet {$customer->package->name} - " . \Carbon\Carbon::parse($billingMonth)->format('F Y'),
                        'status' => 'unpaid',
                        'due_date' => $dueDate,
                        'invoice_number' => Invoice::generateInvoiceNumber(),
                        'invoice_type' => 'monthly',
                    ]);

                    // Update customer debt
                    $customer->total_debt += ($amount + $taxAmount);
                    $customer->updateUnpaidInvoicesCount();
                    $customer->save();

                    $generated++;

                    // Send WhatsApp notification if requested
                    if ($sendNotification && $customer->phone) {
                        try {
                            $this->whatsapp->sendInvoiceNotification($customer, $invoice);
                            $notifications++;
                        } catch (\Exception $e) {
                            \Log::warning("Failed to send invoice notification to {$customer->phone}: " . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    \Log::error("Failed to generate invoice for customer {$customer->id}: " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal generate invoice: ' . $e->getMessage());
        }

        $message = "Berhasil generate {$generated} invoice";
        if ($skipped > 0) $message .= ", {$skipped} di-skip (sudah ada)";
        if ($errors > 0) $message .= ", {$errors} gagal";
        if ($notifications > 0) $message .= ", {$notifications} notifikasi terkirim";

        return redirect()->route('admin.invoices.index')->with('success', $message);
    }

    // ==========================================
    // DEBT REPORT & MANAGEMENT
    // ==========================================

    /**
     * Debt report / Laporan Hutang
     */
    public function debtReport(Request $request)
    {
        $query = Customer::with(['package', 'unpaidInvoices'])
            ->where('total_debt', '>', 0);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by package
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        // Filter by min debt
        if ($request->filled('min_debt')) {
            $query->where('total_debt', '>=', $request->min_debt);
        }

        // Filter by unpaid count
        if ($request->filled('min_unpaid_count')) {
            $query->where('unpaid_invoices_count', '>=', $request->min_unpaid_count);
        }

        $customers = $query->orderBy('total_debt', 'desc')->paginate(20);
        $packages = Package::where('is_active', true)->get();

        // Summary stats
        $stats = [
            'total_customers_with_debt' => Customer::where('total_debt', '>', 0)->count(),
            'total_debt' => Customer::sum('total_debt'),
            'customers_3_or_more' => Customer::where('unpaid_invoices_count', '>=', 3)->count(),
            'customers_with_installment' => Customer::where('has_installment_plan', true)->count(),
            'aging' => $this->getDebtAgingSummary(),
        ];

        return view('admin.invoices.debt-report', compact('customers', 'packages', 'stats'));
    }

    /**
     * Get debt aging summary
     */
    protected function getDebtAgingSummary()
    {
        $aging = [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            'over_90' => 0,
        ];

        $unpaidInvoices = Invoice::where('status', 'unpaid')->get();

        foreach ($unpaidInvoices as $invoice) {
            $remaining = $invoice->remaining_balance;

            if (!$invoice->due_date || $invoice->due_date->isFuture()) {
                $aging['current'] += $remaining;
            } else {
                $daysOverdue = $invoice->due_date->diffInDays(now());

                if ($daysOverdue <= 30) {
                    $aging['1-30'] += $remaining;
                } elseif ($daysOverdue <= 60) {
                    $aging['31-60'] += $remaining;
                } elseif ($daysOverdue <= 90) {
                    $aging['61-90'] += $remaining;
                } else {
                    $aging['over_90'] += $remaining;
                }
            }
        }

        return $aging;
    }

    /**
     * Customer debt detail
     */
    public function customerDebt(Customer $customer)
    {
        $customer->load(['package', 'unpaidInvoices.package', 'paymentHistories', 'activeInstallmentPlan']);

        $unpaidInvoices = $customer->invoices()
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();

        $paidInvoices = $customer->invoices()
            ->where('status', 'paid')
            ->orderBy('paid_date', 'desc')
            ->limit(10)
            ->get();

        $paymentHistory = $customer->paymentHistories()
            ->with(['invoice', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.invoices.customer-debt', compact('customer', 'unpaidInvoices', 'paidInvoices', 'paymentHistory'));
    }

    // ==========================================
    // PAYMENT WITH DEBT REDUCTION
    // ==========================================

    /**
     * Record payment (with debt reduction)
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $amount = $request->amount;
        $maxAmount = $invoice->remaining_balance;

        if ($amount > $maxAmount) {
            return redirect()->back()->with('error', "Jumlah pembayaran melebihi sisa tagihan (Rp " . number_format($maxAmount, 0, ',', '.') . ")");
        }

        DB::beginTransaction();
        try {
            // Record payment using Invoice model method
            $payment = $invoice->recordPayment(
                $amount,
                $request->payment_method,
                auth()->id(),
                $request->notes
            );

            // Fire event if fully paid
            if ($invoice->status === 'paid') {
                event(new InvoicePaid($invoice));
            }

            // Check if customer should be reactivated
            $customer = $invoice->customer;
            if ($customer->status === 'suspended' && !$customer->shouldBeIsolated()) {
                $customer->reactivate();
            }

            DB::commit();

            $message = "Pembayaran Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat.";
            if ($invoice->status === 'paid') {
                $message .= " Invoice lunas.";
            } else {
                $message .= " Sisa: Rp " . number_format($invoice->remaining_balance, 0, ',', '.');
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    // ==========================================
    // INSTALLMENT PLAN
    // ==========================================

    /**
     * Create installment plan form
     */
    public function createInstallmentForm(Invoice $invoice)
    {
        $invoice->load('customer');

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Invoice sudah lunas');
        }

        return view('admin.invoices.create-installment', compact('invoice'));
    }

    /**
     * Store installment plan
     */
    public function storeInstallment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'number_of_installments' => 'required|integer|min:2|max:12',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Invoice sudah lunas');
        }

        $customer = $invoice->customer;
        $totalAmount = $invoice->remaining_balance;
        $numberOfInstallments = $request->number_of_installments;
        $installmentAmount = ceil($totalAmount / $numberOfInstallments);

        DB::beginTransaction();
        try {
            // Create installment plan
            $plan = InstallmentPlan::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'total_amount' => $totalAmount,
                'installment_amount' => $installmentAmount,
                'number_of_installments' => $numberOfInstallments,
                'remaining_amount' => $totalAmount,
                'start_date' => $request->start_date,
                'end_date' => \Carbon\Carbon::parse($request->start_date)->addMonths($numberOfInstallments - 1),
                'status' => 'active',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Generate installment invoices
            $plan->generateInstallmentInvoices();

            // Mark original invoice as converted to installment
            $invoice->update([
                'description' => $invoice->description . ' [Dikonversi ke cicilan]',
            ]);

            // Update customer
            $customer->has_installment_plan = true;
            $customer->save();

            // If customer was suspended, reactivate them
            if ($customer->status === 'suspended') {
                $customer->reactivate();
            }

            DB::commit();

            return redirect()->route('admin.invoices.customer-debt', $customer)
                ->with('success', "Cicilan {$numberOfInstallments}x berhasil dibuat. Pelanggan tetap aktif selama cicilan berjalan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat cicilan: ' . $e->getMessage());
        }
    }

    // ==========================================
    // RECALCULATE DEBTS
    // ==========================================

    /**
     * Recalculate all customer debts
     */
    public function recalculateAllDebts()
    {
        $customers = Customer::all();
        $updated = 0;

        foreach ($customers as $customer) {
            $customer->recalculateDebt();
            $updated++;
        }

        return redirect()->back()->with('success', "Berhasil recalculate hutang {$updated} pelanggan");
    }
}
