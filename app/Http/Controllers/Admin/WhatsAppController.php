<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WhatsappLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * WhatsApp dashboard
     */
    public function index()
    {
        $status = $this->whatsapp->checkStatus();
        $connected = $status && isset($status['connected']) && $status['connected'] === true;
        
        // Get recent logs for dashboard
        $recentLogs = WhatsappLog::with(['customer', 'invoice'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get stats
        $stats = [
            'total' => WhatsappLog::count(),
            'sent' => WhatsappLog::where('status', 'sent')->count(),
            'failed' => WhatsappLog::where('status', 'failed')->count(),
            'today' => WhatsappLog::whereDate('created_at', today())->count(),
        ];
        
        return view('admin.whatsapp.index', [
            'connected' => $connected,
            'status' => $status,
            'recentLogs' => $recentLogs,
            'stats' => $stats
        ]);
    }

    /**
     * WhatsApp notification logs
     */
    public function logs(Request $request)
    {
        $query = WhatsappLog::with(['customer', 'invoice'])->latest();
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        // Search by phone or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $logs = $query->paginate(20)->withQueryString();
        
        // Stats
        $stats = [
            'total' => WhatsappLog::count(),
            'sent' => WhatsappLog::where('status', 'sent')->count(),
            'failed' => WhatsappLog::where('status', 'failed')->count(),
            'today' => WhatsappLog::whereDate('created_at', today())->count(),
        ];
        
        return view('admin.whatsapp.logs', compact('logs', 'stats'));
    }

    /**
     * Resend failed notification
     */
    public function resend(WhatsappLog $log)
    {
        $customer = $log->customer;
        $invoice = $log->invoice;
        
        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }
        
        $result = null;
        
        switch ($log->type) {
            case 'invoice':
                if ($invoice) {
                    $result = $this->whatsapp->sendInvoiceNotification($customer, $invoice);
                }
                break;
            case 'reminder':
                if ($invoice) {
                    $result = $this->whatsapp->sendPaymentReminder($customer, $invoice);
                }
                break;
            case 'suspension':
                $result = $this->whatsapp->sendSuspensionNotice($customer);
                break;
            default:
                $result = $this->whatsapp->send($customer->phone, $log->message);
        }
        
        if ($result && $result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim ulang'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Gagal mengirim ulang pesan'
        ]);
    }

    /**
     * Send custom message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        $result = $this->whatsapp->send($validated['phone'], $validated['message']);

        if ($result['success']) {
            return back()->with('success', 'Pesan berhasil dikirim');
        }

        return back()->with('error', 'Gagal mengirim pesan: ' . ($result['message'] ?? 'Unknown error'));
    }

    /**
     * Send invoice notification
     */
    public function sendInvoice(Invoice $invoice)
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
     * Send payment reminder
     */
    public function sendReminder(Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }

        $result = $this->whatsapp->sendPaymentReminder($customer, $invoice);

        return response()->json($result);
    }

    /**
     * Bulk send invoice notifications
     */
    public function bulkSendInvoice(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        
        if (empty($invoiceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No invoices selected'
            ], 400);
        }

        $invoices = Invoice::with('customer', 'package')
            ->whereIn('id', $invoiceIds)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            
            if ($customer && $customer->phone) {
                $result = $this->whatsapp->sendInvoiceNotification($customer, $invoice);
                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent: {$sent}, Failed: {$failed}",
            'sent' => $sent,
            'failed' => $failed
        ]);
    }

    /**
     * Bulk send payment reminders
     */
    public function bulkSendReminder(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        
        if (empty($invoiceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No invoices selected'
            ], 400);
        }

        $invoices = Invoice::with('customer', 'package')
            ->whereIn('id', $invoiceIds)
            ->where('status', 'unpaid')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            
            if ($customer && $customer->phone) {
                $result = $this->whatsapp->sendPaymentReminder($customer, $invoice);
                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent: {$sent}, Failed: {$failed}",
            'sent' => $sent,
            'failed' => $failed
        ]);
    }

    /**
     * Check WhatsApp status
     */
    public function status()
    {
        $status = $this->whatsapp->checkStatus();
        $connected = $status && isset($status['connected']) && $status['connected'] === true;

        return response()->json([
            'connected' => $connected,
            'data' => $status
        ]);
    }

    /**
     * Test page - send test notification to selected customer
     */
    public function test()
    {
        $customers = Customer::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->with('package')
            ->orderBy('name')
            ->get();

        return view('admin.whatsapp.test', compact('customers'));
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|in:invoice,reminder,suspension,custom',
            'message' => 'nullable|string|max:4096',
        ]);

        $customer = Customer::with(['package', 'invoices' => function($q) {
            $q->latest()->first();
        }])->findOrFail($validated['customer_id']);

        if (!$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak memiliki nomor telepon'
            ], 400);
        }

        $result = null;
        $invoice = $customer->invoices->first();

        switch ($validated['type']) {
            case 'invoice':
                if (!$invoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pelanggan tidak memiliki invoice'
                    ], 400);
                }
                $result = $this->whatsapp->sendInvoiceNotification($customer, $invoice);
                break;

            case 'reminder':
                if (!$invoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pelanggan tidak memiliki invoice'
                    ], 400);
                }
                $result = $this->whatsapp->sendPaymentReminder($customer, $invoice);
                break;

            case 'suspension':
                $result = $this->whatsapp->sendSuspensionNotice($customer);
                break;

            case 'custom':
                if (empty($validated['message'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pesan tidak boleh kosong'
                    ], 400);
                }
                $result = $this->whatsapp->send($customer->phone, $validated['message']);
                break;
        }

        return response()->json($result);
    }
}
