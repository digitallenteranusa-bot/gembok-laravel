<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\VoucherOnlineSetting;
use App\Models\VoucherPurchase;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $agent = Agent::where('username', $request->username)->first();

        if ($agent && Hash::check($request->password, $agent->password)) {
            Auth::loginUsingId($agent->user_id ?? $agent->id);
            session(['agent_id' => $agent->id]);
            return redirect()->route('agent.dashboard');
        }

        return back()->with('error', 'Username atau password salah');
    }

    public function logout()
    {
        session()->forget('agent_id');
        Auth::logout();
        return redirect()->route('agent.login');
    }

    public function dashboard()
    {
        $agent = Agent::find(session('agent_id'));
        
        $todaySales = VoucherPurchase::where('agent_id', $agent->id ?? 0)
            ->whereDate('created_at', today())
            ->count();

        $monthSales = VoucherPurchase::where('agent_id', $agent->id ?? 0)
            ->whereMonth('created_at', now()->month)
            ->count();

        $monthCommission = VoucherPurchase::where('agent_id', $agent->id ?? 0)
            ->whereMonth('created_at', now()->month)
            ->sum('commission');

        $voucherPackages = VoucherOnlineSetting::where('is_active', true)->get();

        $recentTransactions = VoucherPurchase::where('agent_id', $agent->id ?? 0)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('agent.dashboard', compact(
            'agent', 'todaySales', 'monthSales', 'monthCommission', 
            'voucherPackages', 'recentTransactions'
        ));
    }

    public function sellVoucher(Request $request)
    {
        $agent = Agent::find(session('agent_id'));
        $packages = VoucherOnlineSetting::where('is_active', true)->get();
        $selectedPackage = $request->get('package');

        return view('agent.sell-voucher', compact('agent', 'packages', 'selectedPackage'));
    }

    public function processSale(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:voucher_online_settings,id',
            'customer_phone' => 'required',
            'customer_name' => 'nullable',
        ]);

        $agent = Agent::find(session('agent_id'));
        $package = VoucherOnlineSetting::findOrFail($request->package_id);

        // Check agent balance
        if ($agent->balance < $package->agent_price) {
            return back()->with('error', 'Saldo tidak mencukupi. Silakan top up terlebih dahulu.');
        }

        // Generate voucher code
        $voucherCode = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

        // Create purchase record
        $purchase = VoucherPurchase::create([
            'agent_id' => $agent->id,
            'voucher_setting_id' => $package->id,
            'voucher_code' => $voucherCode,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'amount' => $package->customer_price,
            'agent_price' => $package->agent_price,
            'commission' => $package->customer_price - $package->agent_price,
            'status' => 'completed',
        ]);

        // Deduct agent balance
        $agent->decrement('balance', $package->agent_price);

        // Send voucher via WhatsApp
        try {
            $whatsapp = new WhatsAppService();
            $whatsapp->sendVoucher($request->customer_phone, $voucherCode, $package->name, $package->duration);
        } catch (\Exception $e) {
            // Log error but don't fail the transaction
        }

        return redirect()->route('agent.dashboard')
            ->with('success', "Voucher berhasil dijual! Kode: {$voucherCode}");
    }

    public function topup()
    {
        $agent = Agent::find(session('agent_id'));
        return view('agent.topup', compact('agent'));
    }

    public function processTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50000',
        ]);

        $agent = Agent::find(session('agent_id'));

        // Create balance request (pending approval)
        // TODO: Create AgentBalanceRequest model
        
        return back()->with('success', 'Permintaan top up berhasil dikirim. Menunggu konfirmasi admin.');
    }

    public function transactions()
    {
        $agent = Agent::find(session('agent_id'));
        $transactions = VoucherPurchase::where('agent_id', $agent->id ?? 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('agent.transactions', compact('agent', 'transactions'));
    }

    public function profile()
    {
        $agent = Agent::find(session('agent_id'));
        return view('agent.profile', compact('agent'));
    }

    public function updateProfile(Request $request)
    {
        $agent = Agent::find(session('agent_id'));
        
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
        ]);

        $agent->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        if ($request->filled('password')) {
            $agent->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui');
    }
}
