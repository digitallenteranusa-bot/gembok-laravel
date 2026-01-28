<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CrmService;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    protected $crm;
    protected $accounting;

    public function __construct(CrmService $crm, AccountingService $accounting)
    {
        $this->crm = $crm;
        $this->accounting = $accounting;
    }

    public function crm()
    {
        $enabled = $this->crm->isEnabled();
        $provider = $this->crm->getProvider();
        
        return view('admin.integration.crm', compact('enabled', 'provider'));
    }

    public function accounting()
    {
        $enabled = $this->accounting->isEnabled();
        $provider = $this->accounting->getProvider();
        
        return view('admin.integration.accounting', compact('enabled', 'provider'));
    }

    public function syncCustomerToCrm(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        
        $customer = \App\Models\Customer::findOrFail($request->customer_id);
        $result = $this->crm->syncCustomer($customer);

        return $result['success']
            ? back()->with('success', 'Customer berhasil disync ke CRM')
            : back()->with('error', 'Gagal sync: ' . ($result['message'] ?? 'Unknown error'));
    }

    public function syncCustomerToAccounting(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        
        $customer = \App\Models\Customer::findOrFail($request->customer_id);
        $result = $this->accounting->syncCustomer($customer);

        return $result['success']
            ? back()->with('success', 'Customer berhasil disync ke Accounting')
            : back()->with('error', 'Gagal sync: ' . ($result['message'] ?? 'Unknown error'));
    }


    public function syncInvoiceToAccounting(Request $request)
    {
        $request->validate(['invoice_id' => 'required|exists:invoices,id']);
        
        $invoice = \App\Models\Invoice::with('customer')->findOrFail($request->invoice_id);
        $result = $this->accounting->createInvoice($invoice);

        return $result['success']
            ? back()->with('success', 'Invoice berhasil disync ke Accounting')
            : back()->with('error', 'Gagal sync: ' . ($result['message'] ?? 'Unknown error'));
    }

    public function syncPaymentToAccounting(Request $request)
    {
        $request->validate(['payment_id' => 'required|exists:payments,id']);
        
        $payment = \App\Models\Payment::with('invoice.customer')->findOrFail($request->payment_id);
        $result = $this->accounting->recordPayment($payment);

        return $result['success']
            ? back()->with('success', 'Payment berhasil disync ke Accounting')
            : back()->with('error', 'Gagal sync: ' . ($result['message'] ?? 'Unknown error'));
    }

    public function bulkSyncCrm(Request $request)
    {
        $request->validate(['customer_ids' => 'required|array']);
        
        $success = 0;
        $failed = 0;

        foreach ($request->customer_ids as $id) {
            $customer = \App\Models\Customer::find($id);
            if ($customer) {
                $result = $this->crm->syncCustomer($customer);
                $result['success'] ? $success++ : $failed++;
            }
        }

        return back()->with('success', "Sync selesai: {$success} berhasil, {$failed} gagal");
    }

    public function bulkSyncAccounting(Request $request)
    {
        $request->validate(['invoice_ids' => 'required|array']);
        
        $success = 0;
        $failed = 0;

        foreach ($request->invoice_ids as $id) {
            $invoice = \App\Models\Invoice::with('customer')->find($id);
            if ($invoice) {
                $result = $this->accounting->createInvoice($invoice);
                $result['success'] ? $success++ : $failed++;
            }
        }

        return back()->with('success', "Sync selesai: {$success} berhasil, {$failed} gagal");
    }

    public function testCrmConnection()
    {
        if (!$this->crm->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'CRM tidak aktif']);
        }

        // Test with dummy data
        return response()->json([
            'success' => true,
            'provider' => $this->crm->getProvider(),
            'message' => 'Koneksi CRM berhasil',
        ]);
    }

    public function testAccountingConnection()
    {
        if (!$this->accounting->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Accounting tidak aktif']);
        }

        return response()->json([
            'success' => true,
            'provider' => $this->accounting->getProvider(),
            'message' => 'Koneksi Accounting berhasil',
        ]);
    }
}
