<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotspotProfile;
use App\Models\HotspotVoucher;
use App\Models\HotspotSyncLog;
use App\Services\HotspotSyncService;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotspotController extends Controller
{
    protected HotspotSyncService $syncService;

    public function __construct(HotspotSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    // ==================== DASHBOARD ====================

    public function index()
    {
        $stats = [
            'total_profiles' => HotspotProfile::count(),
            'active_profiles' => HotspotProfile::where('is_active', true)->count(),
            'total_vouchers' => HotspotVoucher::count(),
            'unused_vouchers' => HotspotVoucher::where('status', 'unused')->count(),
            'used_vouchers' => HotspotVoucher::where('status', 'used')->count(),
            'expired_vouchers' => HotspotVoucher::where('status', 'expired')->count(),
            'unsynced_profiles' => HotspotProfile::where('synced', false)->count(),
            'unsynced_vouchers' => HotspotVoucher::where('synced', false)->count(),
        ];

        $recentLogs = HotspotSyncLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();
        $mikrotikIdentity = $mikrotikConnected ? $mikrotik->getSystemIdentity() : null;

        return view('admin.hotspot.index', compact('stats', 'recentLogs', 'mikrotikConnected', 'mikrotikIdentity'));
    }

    // ==================== PROFILES ====================

    public function profiles(Request $request)
    {
        $query = HotspotProfile::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('synced')) {
            $query->where('synced', $request->synced === 'yes');
        }

        $profiles = $query->withCount('vouchers')->latest()->paginate(20);

        return view('admin.hotspot.profiles', compact('profiles'));
    }

    public function createProfile()
    {
        return view('admin.hotspot.profile-form');
    }

    public function storeProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name',
            'rate_limit' => 'nullable|string|max:50',
            'shared_users' => 'nullable|integer|min:1',
            'session_timeout' => 'nullable|string|max:20',
            'idle_timeout' => 'nullable|string|max:20',
            'address_pool' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'agent_price' => 'nullable|numeric|min:0',
            'validity' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sync_to_mikrotik' => 'boolean',
        ]);

        // Parse rate limit to get speeds
        $mikrotik = app(MikrotikService::class);
        $speeds = $mikrotik->parseRateLimit($validated['rate_limit'] ?? '');

        $profile = HotspotProfile::create([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'],
            'upload_speed' => $speeds['upload'],
            'download_speed' => $speeds['download'],
            'shared_users' => $validated['shared_users'] ?? 1,
            'session_timeout' => $validated['session_timeout'],
            'idle_timeout' => $validated['idle_timeout'],
            'address_pool' => $validated['address_pool'],
            'price' => $validated['price'] ?? 0,
            'agent_price' => $validated['agent_price'] ?? 0,
            'validity' => $validated['validity'],
            'is_active' => $validated['is_active'] ?? true,
            'synced' => false,
        ]);

        if ($request->boolean('sync_to_mikrotik')) {
            $this->syncService->pushProfiles();
        }

        return redirect()->route('admin.hotspot.profiles')
            ->with('success', 'Profile created successfully.');
    }

    public function editProfile(HotspotProfile $profile)
    {
        return view('admin.hotspot.profile-form', compact('profile'));
    }

    public function updateProfile(Request $request, HotspotProfile $profile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name,' . $profile->id,
            'rate_limit' => 'nullable|string|max:50',
            'shared_users' => 'nullable|integer|min:1',
            'session_timeout' => 'nullable|string|max:20',
            'idle_timeout' => 'nullable|string|max:20',
            'address_pool' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'agent_price' => 'nullable|numeric|min:0',
            'validity' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sync_to_mikrotik' => 'boolean',
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
            'idle_timeout' => $validated['idle_timeout'],
            'address_pool' => $validated['address_pool'],
            'price' => $validated['price'] ?? 0,
            'agent_price' => $validated['agent_price'] ?? 0,
            'validity' => $validated['validity'],
            'is_active' => $validated['is_active'] ?? true,
            'synced' => false,
        ]);

        if ($request->boolean('sync_to_mikrotik')) {
            $this->syncService->pushProfiles();
        }

        return redirect()->route('admin.hotspot.profiles')
            ->with('success', 'Profile updated successfully.');
    }

    public function deleteProfile(HotspotProfile $profile)
    {
        if ($profile->vouchers()->count() > 0) {
            return back()->with('error', 'Cannot delete profile with existing vouchers.');
        }

        $this->syncService->deleteProfileFromMikrotik($profile);
        $profile->delete();

        return redirect()->route('admin.hotspot.profiles')
            ->with('success', 'Profile deleted successfully.');
    }

    // ==================== VOUCHERS ====================

    public function vouchers(Request $request)
    {
        $query = HotspotVoucher::with('profile');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                  ->orWhere('comment', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('profile_id')) {
            $query->where('profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('synced')) {
            $query->where('synced', $request->synced === 'yes');
        }

        $vouchers = $query->latest()->paginate(50);
        $profiles = HotspotProfile::where('is_active', true)->get();

        return view('admin.hotspot.vouchers', compact('vouchers', 'profiles'));
    }

    public function generateVouchers()
    {
        $profiles = HotspotProfile::where('is_active', true)->get();
        return view('admin.hotspot.generate', compact('profiles'));
    }

    public function storeVouchers(Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:1000',
            'profile_id' => 'required|exists:hotspot_profiles,id',
            'prefix' => 'nullable|string|max:10',
            'length' => 'nullable|integer|min:4|max:12',
            'password_length' => 'nullable|integer|min:4|max:12',
            'limit_uptime' => 'nullable|string|max:20',
            'limit_bytes' => 'nullable|integer|min:0',
            'server' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:255',
            'sync_to_mikrotik' => 'boolean',
        ]);

        $result = $this->syncService->generateVouchers([
            'quantity' => $validated['quantity'],
            'profile_id' => $validated['profile_id'],
            'prefix' => $validated['prefix'] ?? 'VC',
            'length' => $validated['length'] ?? 6,
            'password_length' => $validated['password_length'] ?? 6,
            'limit_uptime' => $validated['limit_uptime'],
            'limit_bytes' => $validated['limit_bytes'],
            'server' => $validated['server'] ?? 'all',
            'comment' => $validated['comment'] ?? 'Generated by Gembok',
            'sync_to_mikrotik' => $request->boolean('sync_to_mikrotik', true),
        ]);

        if ($request->boolean('print_vouchers')) {
            return view('admin.hotspot.print-vouchers', [
                'vouchers' => $result['vouchers'],
                'profile' => HotspotProfile::find($validated['profile_id']),
            ]);
        }

        return redirect()->route('admin.hotspot.vouchers')
            ->with('success', "Generated {$result['created']} vouchers successfully.");
    }

    public function deleteVoucher(HotspotVoucher $voucher)
    {
        $this->syncService->deleteVoucherFromMikrotik($voucher);
        $voucher->delete();

        return redirect()->route('admin.hotspot.vouchers')
            ->with('success', 'Voucher deleted successfully.');
    }

    public function bulkDeleteVouchers(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:hotspot_vouchers,id',
            'delete_from_mikrotik' => 'boolean',
        ]);

        $result = $this->syncService->deleteVouchers(
            $validated['ids'],
            $request->boolean('delete_from_mikrotik', true)
        );

        return redirect()->route('admin.hotspot.vouchers')
            ->with('success', "Deleted {$result['deleted']} vouchers.");
    }

    public function printVouchers(Request $request)
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

    public function sync()
    {
        $profiles = HotspotProfile::count();
        $vouchers = HotspotVoucher::count();
        $unsyncedProfiles = HotspotProfile::where('synced', false)->count();
        $unsyncedVouchers = HotspotVoucher::where('synced', false)->count();

        $mikrotik = app(MikrotikService::class);
        $mikrotikConnected = $mikrotik->isConnected();

        $recentLogs = HotspotSyncLog::with('user')->latest()->take(20)->get();

        return view('admin.hotspot.sync', compact(
            'profiles', 'vouchers', 'unsyncedProfiles', 'unsyncedVouchers',
            'mikrotikConnected', 'recentLogs'
        ));
    }

    public function doSync(Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|in:pull,push,full',
            'type' => 'required|in:profile,voucher,all',
            'conflict_resolution' => 'nullable|in:mikrotik,gembok',
        ]);

        $direction = $validated['direction'];
        $type = $validated['type'];
        $conflictResolution = $validated['conflict_resolution'] ?? 'mikrotik';

        $result = [];

        if ($direction === 'full') {
            $result = $this->syncService->fullSync($type, $conflictResolution);
        } elseif ($direction === 'pull') {
            if ($type === 'all' || $type === 'profile') {
                $result['profiles'] = $this->syncService->pullProfiles();
            }
            if ($type === 'all' || $type === 'voucher') {
                $result['vouchers'] = $this->syncService->pullVouchers();
            }
        } elseif ($direction === 'push') {
            if ($type === 'all' || $type === 'profile') {
                $result['profiles'] = $this->syncService->pushProfiles();
            }
            if ($type === 'all' || $type === 'voucher') {
                $result['vouchers'] = $this->syncService->pushVouchers();
            }
        }

        $message = $this->buildSyncMessage($result);

        return redirect()->route('admin.hotspot.sync')
            ->with('success', $message);
    }

    protected function buildSyncMessage(array $result): string
    {
        $messages = [];

        if (isset($result['profiles'])) {
            $p = $result['profiles'];
            if (isset($p['pull'])) {
                $messages[] = "Profiles Pull: {$p['pull']['created']} created, {$p['pull']['updated']} updated";
            }
            if (isset($p['push'])) {
                $messages[] = "Profiles Push: {$p['push']['created']} created, {$p['push']['updated']} updated";
            }
            if (isset($p['created'])) {
                $messages[] = "Profiles: {$p['created']} created, {$p['updated']} updated";
            }
        }

        if (isset($result['vouchers'])) {
            $v = $result['vouchers'];
            if (isset($v['pull'])) {
                $messages[] = "Vouchers Pull: {$v['pull']['created']} created, {$v['pull']['updated']} updated";
            }
            if (isset($v['push'])) {
                $messages[] = "Vouchers Push: {$v['push']['created']} created, {$v['push']['updated']} updated";
            }
            if (isset($v['created'])) {
                $messages[] = "Vouchers: {$v['created']} created, {$v['updated']} updated";
            }
        }

        return implode('. ', $messages) ?: 'Sync completed.';
    }

    // ==================== LOGS ====================

    public function logs(Request $request)
    {
        $query = HotspotSyncLog::with('user');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->latest()->paginate(50);

        return view('admin.hotspot.logs', compact('logs'));
    }
}
