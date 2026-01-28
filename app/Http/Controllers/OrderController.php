<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Services\PaymentGatewayService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $payment;
    protected $whatsapp;

    public function __construct(PaymentGatewayService $payment, WhatsAppService $whatsapp)
    {
        $this->payment = $payment;
        $this->whatsapp = $whatsapp;
    }

    /**
     * Show order form
     */
    public function create(Package $package)
    {
        return view('order.create', compact('package'));
    }

    /**
     * Store new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'required|string',
            'customer_nik' => 'nullable|string|max:20',
            'connection_type' => 'required|in:pppoe,hotspot',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'customer_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        
        // Calculate pricing
        $installationFee = 150000; // Default installation fee
        $packagePrice = $package->price;
        $totalAmount = $packagePrice + $installationFee;

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'customer_address' => $validated['customer_address'],
            'customer_nik' => $validated['customer_nik'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'package_id' => $package->id,
            'connection_type' => $validated['connection_type'],
            'package_price' => $packagePrice,
            'installation_fee' => $installationFee,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'customer_notes' => $validated['customer_notes'],
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Create payment if using payment gateway
        if ($validated['payment_method'] === 'midtrans') {
            $paymentResult = $this->payment->createTransaction([
                'order_id' => $order->order_number,
                'gross_amount' => $totalAmount,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email ?? 'customer@example.com',
                'customer_phone' => $order->customer_phone,
                'item_details' => [
                    [
                        'id' => 'PKG-' . $package->id,
                        'name' => 'Paket ' . $package->name,
                        'price' => $packagePrice,
                        'quantity' => 1,
                    ],
                    [
                        'id' => 'INSTALL',
                        'name' => 'Biaya Pemasangan',
                        'price' => $installationFee,
                        'quantity' => 1,
                    ],
                ],
            ]);

            if ($paymentResult['success']) {
                $order->update([
                    'payment_url' => $paymentResult['redirect_url'] ?? null,
                    'payment_transaction_id' => $paymentResult['token'] ?? null,
                ]);

                return redirect()->away($paymentResult['redirect_url']);
            }
        }

        // Send WhatsApp notification to admin
        $this->notifyAdmin($order);

        return redirect()->route('order.success', $order->order_number);
    }

    /**
     * Order success page
     */
    public function success($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('package')->firstOrFail();
        return view('order.success', compact('order'));
    }

    /**
     * Track order status
     */
    public function track(Request $request)
    {
        $order = null;
        
        if ($request->filled('order_number')) {
            $order = Order::where('order_number', $request->order_number)
                ->orWhere('customer_phone', 'like', '%' . $request->order_number . '%')
                ->with('package', 'technician')
                ->first();
        }

        return view('order.track', compact('order'));
    }

    /**
     * Notify admin about new order
     */
    protected function notifyAdmin(Order $order)
    {
        $adminPhone = config('services.whatsapp.admin_phone');
        
        if ($adminPhone) {
            $message = "🆕 *Pesanan Baru!*\n\n";
            $message .= "📋 *Order:* {$order->order_number}\n";
            $message .= "👤 *Nama:* {$order->customer_name}\n";
            $message .= "📱 *Telepon:* {$order->customer_phone}\n";
            $message .= "📦 *Paket:* {$order->package->name}\n";
            $message .= "💰 *Total:* Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
            $message .= "🔌 *Tipe:* " . strtoupper($order->connection_type) . "\n\n";
            $message .= "📍 *Alamat:*\n{$order->customer_address}";

            $this->whatsapp->send($adminPhone, $message);
        }
    }
}
