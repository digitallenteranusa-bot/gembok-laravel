<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerApiController extends Controller
{
    /**
     * Customer Login
     * @group Customer Authentication
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::where('pppoe_username', $request->username)
            ->orWhere('phone', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (!$customer || $request->password !== $customer->pppoe_password) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
            ],
        ]);
    }

    /**
     * Get Customer Profile
     * @group Customer
     */
    public function profile(Request $request)
    {
        $customer = $request->user();
        $customer->load('package');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'status' => $customer->status,
                'package' => $customer->package ? [
                    'name' => $customer->package->name,
                    'speed' => $customer->package->speed,
                    'price' => $customer->package->price,
                ] : null,
                'registration_date' => $customer->created_at->format('Y-m-d'),
            ],
        ]);
    }


    /**
     * Get Customer Invoices
     * @group Customer
     */
    public function invoices(Request $request)
    {
        $customer = $request->user();
        $status = $request->get('status'); // paid, unpaid, all
        
        $query = Invoice::where('customer_id', $customer->id);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $invoices->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'amount' => $inv->amount,
                'total' => $inv->total,
                'status' => $inv->status,
                'due_date' => $inv->due_date->format('Y-m-d'),
                'paid_at' => $inv->paid_at?->format('Y-m-d H:i:s'),
                'created_at' => $inv->created_at->format('Y-m-d'),
            ]),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Get Invoice Detail
     * @group Customer
     */
    public function invoiceDetail(Request $request, $id)
    {
        $customer = $request->user();
        $invoice = Invoice::where('customer_id', $customer->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'description' => $invoice->description,
                'amount' => $invoice->amount,
                'tax' => $invoice->tax,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'paid_at' => $invoice->paid_at?->format('Y-m-d H:i:s'),
                'payment_method' => $invoice->payment_method,
                'payment_url' => $invoice->payment_url,
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get Customer Tickets
     * @group Customer
     */
    public function tickets(Request $request)
    {
        $customer = $request->user();
        
        $tickets = Ticket::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $tickets->map(fn($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'category' => $t->category,
                'priority' => $t->priority,
                'status' => $t->status,
                'created_at' => $t->created_at->format('Y-m-d H:i:s'),
            ]),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Create Support Ticket
     * @group Customer
     */
    public function createTicket(Request $request)
    {
        $customer = $request->user();
        
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'category' => 'required|in:billing,technical,general,complaint',
            'priority' => 'in:low,medium,high',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-' . date('Ymd') . '-' . str_pad(Ticket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'subject' => $request->subject,
            'category' => $request->category,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ],
        ], 201);
    }

    /**
     * Update Customer Profile
     * @group Customer
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();
        
        $validator = Validator::make($request->all(), [
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->has('phone')) $customer->phone = $request->phone;
        if ($request->has('email')) $customer->email = $request->email;
        if ($request->has('password')) $customer->pppoe_password = $request->password;
        
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Get Available Packages
     * @group Public
     */
    public function packages()
    {
        $packages = Package::where('is_active', true)->orderBy('price')->get();

        return response()->json([
            'success' => true,
            'data' => $packages->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'speed' => $p->speed,
                'description' => $p->description,
                'price' => $p->price,
            ]),
        ]);
    }

    /**
     * Logout
     * @group Customer Authentication
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
