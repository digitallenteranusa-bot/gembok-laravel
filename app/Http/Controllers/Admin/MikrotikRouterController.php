<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikRouter;
use App\Services\MikrotikServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MikrotikRouterController extends Controller
{
    /**
     * Display a listing of routers
     */
    public function index()
    {
        $routers = MikrotikRouter::withCount('customers')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.mikrotik.routers.index', compact('routers'));
    }

    /**
     * Show the form for creating a new router
     */
    public function create()
    {
        return view('admin.mikrotik.routers.create');
    }

    /**
     * Store a newly created router
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['port'] = $validated['port'] ?? 8728;
        $validated['use_ssl'] = $request->boolean('use_ssl');
        $validated['enabled'] = $request->boolean('enabled', true);
        $validated['is_default'] = $request->boolean('is_default');

        // If this is set as default, unset others
        if ($validated['is_default']) {
            MikrotikRouter::where('is_default', true)->update(['is_default' => false]);
        }

        $router = MikrotikRouter::create($validated);

        // Test connection
        $result = MikrotikServiceFactory::testConnection($router);

        $message = 'Router berhasil ditambahkan.';
        if ($result['success']) {
            $message .= ' Koneksi berhasil ke ' . ($result['identity'] ?? $router->host);
        } else {
            $message .= ' (Warning: ' . $result['message'] . ')';
        }

        return redirect()->route('admin.mikrotik.routers.index')
            ->with('success', $message);
    }

    /**
     * Show the form for editing the router
     */
    public function edit(MikrotikRouter $router)
    {
        return view('admin.mikrotik.routers.edit', compact('router'));
    }

    /**
     * Update the specified router
     */
    public function update(Request $request, MikrotikRouter $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['port'] = $validated['port'] ?? 8728;
        $validated['use_ssl'] = $request->boolean('use_ssl');
        $validated['enabled'] = $request->boolean('enabled', true);
        $validated['is_default'] = $request->boolean('is_default');

        // Don't update password if not provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // If this is set as default, unset others
        if ($validated['is_default'] ?? false) {
            MikrotikRouter::where('id', '!=', $router->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $router->update($validated);

        return redirect()->route('admin.mikrotik.routers.index')
            ->with('success', 'Router berhasil diupdate.');
    }

    /**
     * Remove the specified router
     */
    public function destroy(MikrotikRouter $router)
    {
        // Check if router has customers
        if ($router->customers()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus router yang masih memiliki customer. Pindahkan customer terlebih dahulu.');
        }

        $router->delete();

        return redirect()->route('admin.mikrotik.routers.index')
            ->with('success', 'Router berhasil dihapus.');
    }

    /**
     * Test connection to a router
     */
    public function testConnection(MikrotikRouter $router)
    {
        $result = MikrotikServiceFactory::testConnection($router);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Koneksi berhasil!',
                'identity' => $result['identity'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung: ' . $result['message'],
        ], 500);
    }

    /**
     * Set router as default
     */
    public function setDefault(MikrotikRouter $router)
    {
        $router->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Router ' . $router->name . ' dijadikan default.',
        ]);
    }
}
