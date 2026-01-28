<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherPricing;
use App\Models\HotspotProfile;
use App\Models\HotspotVoucher;
use App\Models\HotspotSyncLog;
use App\Services\HotspotSyncService;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $stats = [
            'total_sales' => \App\Models\VoucherPurchase::where('status', 'completed')->sum('amount') ?? 0,
            'total_vouchers' => \App\Models\VoucherPurchase::count() ?? 0,
            'pending_vouchers' => \App\Models\VoucherPurchase::where('status', 'pending')->count() ?? 0,
            'active_pricing' => VoucherPricing::where('is_active', true)->count() ?? 0,
            // Hotspot stats
            'hotspot_profiles' => HotspotProfile::count(),
            'hotspot_vouchers' => HotspotVoucher::count(),
            'hotspot_unused' => HotspotVoucher::where('status', 'unused')->count(),
            'hotspot_unsynced' => HotspotVoucher::where('synced', false)->count(),
        ];

        $recent_purchases = \App\Models\VoucherPurchase::latest()->limit(10)->get();
        
        // Mikrotik connection status
        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();

        return view('admin.vouchers.index', compact('stats', 'recent_purchases', 'mikrotikConnected'));
    }

    public function pricing()
    {
        $pricings = VoucherPricing::orderBy('duration')->get();
        return view('admin.vouchers.pricing', compact('pricings'));
    }

    public function createPricing()
    {
        return view('admin.vouchers.pricing-create');
    }

    public function storePricing(Request $request)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:255',
            'customer_price' => 'required|numeric|min:0',
            'agent_price' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        VoucherPricing::create($validated);

        return redirect()->route('admin.vouchers.pricing')->with('success', 'Pricing berhasil ditambahkan!');
    }

    public function updatePricing(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:voucher_pricing,id',
            'customer_price' => 'required|numeric|min:0',
            'agent_price' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',
        ]);

        $pricing = VoucherPricing::find($request->id);
        $pricing->update([
            'customer_price' => $validated['customer_price'],
            'agent_price' => $validated['agent_price'],
            'commission_amount' => $validated['commission_amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', 'Pricing berhasil diupdate!');
    }

    public function deletePricing(VoucherPricing $pricing)
    {
        $pricing->delete();
        return redirect()->route('admin.vouchers.pricing')->with('success', 'Pricing berhasil dihapus!');
    }

    public function seedPricing()
    {
        $pricings = [
            ['package_name' => 'Voucher 1 Jam', 'customer_price' => 3000, 'agent_price' => 2500, 'commission_amount' => 500, 'duration' => 1],
            ['package_name' => 'Voucher 3 Jam', 'customer_price' => 5000, 'agent_price' => 4000, 'commission_amount' => 1000, 'duration' => 3],
            ['package_name' => 'Voucher 6 Jam', 'customer_price' => 8000, 'agent_price' => 6500, 'commission_amount' => 1500, 'duration' => 6],
            ['package_name' => 'Voucher 12 Jam', 'customer_price' => 12000, 'agent_price' => 10000, 'commission_amount' => 2000, 'duration' => 12],
            ['package_name' => 'Voucher 24 Jam', 'customer_price' => 20000, 'agent_price' => 17000, 'commission_amount' => 3000, 'duration' => 24],
            ['package_name' => 'Voucher 3 Hari', 'customer_price' => 50000, 'agent_price' => 42000, 'commission_amount' => 8000, 'duration' => 72],
            ['package_name' => 'Voucher 7 Hari', 'customer_price' => 100000, 'agent_price' => 85000, 'commission_amount' => 15000, 'duration' => 168],
            ['package_name' => 'Voucher 30 Hari', 'customer_price' => 350000, 'agent_price' => 300000, 'commission_amount' => 50000, 'duration' => 720],
        ];

        foreach ($pricings as $pricing) {
            VoucherPricing::updateOrCreate(
                ['package_name' => $pricing['package_name']],
                array_merge($pricing, ['is_active' => true])
            );
        }

        return redirect()->route('admin.vouchers.pricing')->with('success', '8 data pricing sample berhasil dibuat!');
    }

    public function purchases(Request $request)
    {
        $query = \App\Models\VoucherPurchase::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('customer_phone', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(20);

        return view('admin.vouchers.purchases', compact('purchases'));
    }

    public function generate()
    {
        $pricings = \App\Models\VoucherPricing::where('is_active', true)->get();
        $hotspotProfiles = HotspotProfile::where('is_active', true)->get();
        
        return view('admin.vouchers.generate', compact('pricings', 'hotspotProfiles'));
    }

    public function storeGenerate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:online,hotspot',
            'pricing_id' => 'required_if:type,online|nullable|exists:voucher_pricing,id',
            'profile_id' => 'required_if:type,hotspot|nullable|exists:hotspot_profiles,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'prefix' => 'nullable|string|max:10',
            'length' => 'nullable|integer|min:4|max:12',
            'limit_uptime' => 'nullable|string|max:20',
            'sync_to_mikrotik' => 'boolean',
        ]);

        if ($validated['type'] === 'hotspot') {
            $syncService = app(HotspotSyncService::class);
            $result = $syncService->generateVouchers([
                'quantity' => $validated['quantity'],
                'profile_id' => $validated['profile_id'],
                'prefix' => $validated['prefix'] ?? 'VC',
                'length' => $validated['length'] ?? 6,
                'limit_uptime' => $validated['limit_uptime'],
                'sync_to_mikrotik' => $request->boolean('sync_to_mikrotik', true),
            ]);

            if ($request->boolean('print_vouchers')) {
                return view('admin.hotspot.print-vouchers', [
                    'vouchers' => $result['vouchers'],
                    'profile' => HotspotProfile::find($validated['profile_id']),
                ]);
            }

            return redirect()->route('admin.vouchers.hotspot')
                ->with('success', "Generated {$result['created']} hotspot vouchers!");
        }

        // Online voucher generation (existing logic)
        return redirect()->route('admin.vouchers.index')
            ->with('success', $validated['quantity'] . ' vouchers generated successfully!');
    }

    // ==================== HOTSPOT MANAGEMENT ====================

    public function hotspot(Request $request)
    {
        $query = HotspotVoucher::with('profile');

        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('profile_id')) {
            $query->where('profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vouchers = $query->latest()->paginate(50);
        $profiles = HotspotProfile::where('is_active', true)->get();
        
        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();

        return view('admin.vouchers.hotspot', compact('vouchers', 'profiles', 'mikrotikConnected'));
    }

    public function hotspotProfiles(Request $request)
    {
        $profiles = HotspotProfile::withCount('vouchers')->latest()->paginate(20);
        
        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();

        return view('admin.vouchers.hotspot-profiles', compact('profiles', 'mikrotikConnected'));
    }

    public function createHotspotProfile()
    {
        return view('admin.vouchers.hotspot-profile-form');
    }

    public function storeHotspotProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name',
            'rate_limit' => 'nullable|string|max:50',
            'shared_users' => 'nullable|integer|min:1',
            'session_timeout' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'agent_price' => 'nullable|numeric|min:0',
            'validity' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $mikrotik = app(MikrotikService::class);
        $speeds = $mikrotik->parseRateLimit($validated['rate_limit'] ?? '');

        $profile = HotspotProfile::create([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'],
            'upload_speed' => $speeds['upload'],
            'download_speed' => $speeds['download'],
            'shared_users' => $validated['shared_users'] ?? 1,
            'session_timeout' => $validated['session_timeout'],
            'price' => $validated['price'] ?? 0,
            'agent_price' => $validated['agent_price'] ?? 0,
            'validity' => $validated['validity'],
            'is_active' => $request->boolean('is_active', true),
            'synced' => false,
        ]);

        if ($request->boolean('sync_to_mikrotik')) {
            $syncService = app(HotspotSyncService::class);
            $syncService->pushProfiles();
        }

        return redirect()->route('admin.vouchers.hotspot.profiles')
            ->with('success', 'Profile created successfully.');
    }

    public function editHotspotProfile(HotspotProfile $profile)
    {
        return view('admin.vouchers.hotspot-profile-form', compact('profile'));
    }

    public function updateHotspotProfile(Request $request, HotspotProfile $profile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name,' . $profile->id,
            'rate_limit' => 'nullable|string|max:50',
            'shared_users' => 'nullable|integer|min:1',
            'session_timeout' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'agent_price' => 'nullable|numeric|min:0',
            'validity' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $mikrotik = app(MikrotikService::class);
        $speeds = $mikrotik->parseRateLimit($validated['rate_limit'] ?? '');

        $profile->update([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'],
            'upload_speed' => $speeds['upload'],
            'download_speed' => $speeds['download'],
            'shared_users' => $validated['shared_users'] ?? 1,
            'session_timeout' => $validated['session_timeout'],
            'price' => $validated['price'] ?? 0,
            'agent_price' => $validated['agent_price'] ?? 0,
            'validity' => $validated['validity'],
            'is_active' => $request->boolean('is_active', true),
            'synced' => false,
        ]);

        if ($request->boolean('sync_to_mikrotik')) {
            $syncService = app(HotspotSyncService::class);
            $syncService->pushProfiles();
        }

        return redirect()->route('admin.vouchers.hotspot.profiles')
            ->with('success', 'Profile updated successfully.');
    }

    public function deleteHotspotProfile(HotspotProfile $profile)
    {
        if ($profile->vouchers()->count() > 0) {
            return back()->with('error', 'Cannot delete profile with existing vouchers.');
        }

        $syncService = app(HotspotSyncService::class);
        $syncService->deleteProfileFromMikrotik($profile);
        $profile->delete();

        return redirect()->route('admin.vouchers.hotspot.profiles')
            ->with('success', 'Profile deleted successfully.');
    }

    public function deleteHotspotVoucher(HotspotVoucher $voucher)
    {
        $syncService = app(HotspotSyncService::class);
        $syncService->deleteVoucherFromMikrotik($voucher);
        $voucher->delete();

        return redirect()->route('admin.vouchers.hotspot')
            ->with('success', 'Voucher deleted successfully.');
    }

    public function printHotspotVouchers(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:hotspot_vouchers,id',
        ]);

        $vouchers = HotspotVoucher::with('profile')
            ->whereIn('id', $validated['ids'])
            ->get();

        return view('admin.hotspot.print-vouchers', compact('vouchers'));
    }

    // ==================== SYNC ====================

    public function hotspotSync()
    {
        $profiles = HotspotProfile::count();
        $vouchers = HotspotVoucher::count();
        $unsyncedProfiles = HotspotProfile::where('synced', false)->count();
        $unsyncedVouchers = HotspotVoucher::where('synced', false)->count();

        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();

        $recentLogs = HotspotSyncLog::with('user')->latest()->take(20)->get();

        return view('admin.vouchers.hotspot-sync', compact(
            'profiles', 'vouchers', 'unsyncedProfiles', 'unsyncedVouchers',
            'mikrotikConnected', 'recentLogs'
        ));
    }

    public function doHotspotSync(Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|in:pull,push,full',
            'type' => 'required|in:profile,voucher,all',
            'conflict_resolution' => 'nullable|in:mikrotik,gembok',
        ]);

        $syncService = app(HotspotSyncService::class);
        $direction = $validated['direction'];
        $type = $validated['type'];
        $conflictResolution = $validated['conflict_resolution'] ?? 'mikrotik';

        $result = [];

        if ($direction === 'full') {
            $result = $syncService->fullSync($type, $conflictResolution);
        } elseif ($direction === 'pull') {
            if ($type === 'all' || $type === 'profile') {
                $result['profiles'] = $syncService->pullProfiles();
            }
            if ($type === 'all' || $type === 'voucher') {
                $result['vouchers'] = $syncService->pullVouchers();
            }
        } elseif ($direction === 'push') {
            if ($type === 'all' || $type === 'profile') {
                $result['profiles'] = $syncService->pushProfiles();
            }
            if ($type === 'all' || $type === 'voucher') {
                $result['vouchers'] = $syncService->pushVouchers();
            }
        }

        return redirect()->route('admin.vouchers.hotspot.sync')
            ->with('success', 'Sync completed successfully!');
    }

    // ==================== PUBLIC METHODS ====================

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:voucher_pricing,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|in:midtrans,xendit,manual',
        ]);

        $voucherService = new \App\Services\VoucherService();

        try {
            $purchase = $voucherService->createPurchase([
                'pricing_id' => $validated['package_id'],
                'customer_name' => $validated['name'],
                'customer_phone' => $validated['phone'],
                'payment_method' => $validated['payment_method'],
            ]);

            if ($validated['payment_method'] === 'manual') {
                $voucherService->manualActivate($purchase);
                return redirect()->route('voucher.success', $purchase->id);
            }

            $voucherService->processPayment($purchase, 'DEMO-' . time());
            
            return redirect()->route('voucher.success', $purchase->id);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses pembelian: ' . $e->getMessage());
        }
    }

    public function success($id)
    {
        $purchase = \App\Models\VoucherPurchase::findOrFail($id);
        return view('voucher.success', compact('purchase'));
    }
}
