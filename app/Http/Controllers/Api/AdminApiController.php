<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminApiController extends Controller
{
    /**
     * Admin Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    /**
     * Dashboard Stats
     */
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::where('status', 'active')->count(),
                'suspended_customers' => Customer::where('status', 'suspended')->count(),
                'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
                'pending_revenue' => Invoice::where('status', 'unpaid')->sum('total'),
                'paid_invoices' => Invoice::where('status', 'paid')->count(),
                'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
                'total_packages' => Package::where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * List Customers
     */
    public function customers(Request $request)
    {
        $query = Customer::with('package');
        
        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('customer_id', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        
        $customers = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers->map(fn($c) => [
                'id' => $c->id,
                'customer_id' => $c->customer_id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'status' => $c->status,
                'package' => $c->package?->name,
                'created_at' => $c->created_at->format('Y-m-d'),
            ]),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Get Customer Detail
     */
    public function customerDetail($id)
    {
        $customer = Customer::with(['package', 'invoices' => fn($q) => $q->latest()->take(10)])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'status' => $customer->status,
                'pppoe_username' => $customer->pppoe_username,
                'package' => $customer->package,
                'invoices' => $customer->invoices,
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Create Customer
     */
    public function createCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'package_id' => 'required|exists:packages,id',
            'pppoe_username' => 'required|string|unique:customers',
            'pppoe_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::create([
            'customer_id' => 'CUST-' . date('Ymd') . '-' . str_pad(Customer::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'package_id' => $request->package_id,
            'pppoe_username' => $request->pppoe_username,
            'pppoe_password' => $request->pppoe_password,
            'status' => 'active',
        ]);

        return response()->json(['success' => true, 'message' => 'Customer created', 'data' => $customer], 201);
    }

    /**
     * Update Customer
     */
    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|nullable|email',
            'address' => 'sometimes|string',
            'package_id' => 'sometimes|exists:packages,id',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer->update($request->only(['name', 'phone', 'email', 'address', 'package_id', 'status']));

        return response()->json(['success' => true, 'message' => 'Customer updated']);
    }

    /**
     * List Invoices
     */
    public function invoices(Request $request)
    {
        $query = Invoice::with('customer');
        
        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('customer_id')) $query->where('customer_id', $request->customer_id);
        
        $invoices = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $invoices->map(fn($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'customer_name' => $i->customer->name,
                'amount' => $i->amount,
                'total' => $i->total,
                'status' => $i->status,
                'due_date' => $i->due_date->format('Y-m-d'),
            ]),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Mark Invoice as Paid
     */
    public function payInvoice(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_date' => now(),
            'payment_method' => $request->get('payment_method', 'cash'),
        ]);

        return response()->json(['success' => true, 'message' => 'Invoice marked as paid']);
    }

    /**
     * List Packages
     */
    public function packages()
    {
        $packages = Package::withCount('customers')->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }
}
