<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikRouter;
use App\Services\MikrotikService;
use App\Services\MikrotikServiceFactory;
use Illuminate\Http\Request;

class MikrotikController extends Controller
{
    protected function getMikrotikService(?int $routerId = null): MikrotikService
    {
        return MikrotikServiceFactory::forRouterId($routerId);
    }

    public function index(Request $request)
    {
        $routerId = $request->input('router_id');
        $routers = MikrotikRouter::enabled()->orderBy('name')->get();
        $selectedRouter = $routerId ? MikrotikRouter::find($routerId) : MikrotikRouter::getDefault();

        $mikrotik = $this->getMikrotikService($routerId);
        $connected = $mikrotik->isConnected();

        if (!$connected) {
            return view('admin.mikrotik.index', [
                'connected' => false,
                'error' => 'Failed to connect to Mikrotik. Please check your configuration.',
                'pppoeActive' => [],
                'hotspotActive' => [],
                'systemResource' => null,
                'interfaces' => [],
                'stats' => [
                    'pppoe_online' => 0,
                    'hotspot_online' => 0,
                    'total_online' => 0,
                    'cpu_load' => 0,
                    'memory_usage' => 0,
                    'uptime' => 'N/A',
                ],
                'routers' => $routers,
                'selectedRouter' => $selectedRouter,
            ]);
        }

        $pppoeActive = $mikrotik->getPPPoEActive();
        $hotspotActive = $mikrotik->getHotspotActive();
        $systemResource = $mikrotik->getSystemResource();
        $interfaces = $mikrotik->getInterfaces();

        $stats = [
            'pppoe_online' => count($pppoeActive),
            'hotspot_online' => count($hotspotActive),
            'total_online' => count($pppoeActive) + count($hotspotActive),
            'cpu_load' => $systemResource['cpu-load'] ?? 0,
            'memory_usage' => isset($systemResource['free-memory'], $systemResource['total-memory'])
                ? round((($systemResource['total-memory'] - $systemResource['free-memory']) / $systemResource['total-memory']) * 100, 2)
                : 0,
            'uptime' => $systemResource['uptime'] ?? 'N/A',
        ];

        return view('admin.mikrotik.index', compact(
            'connected',
            'pppoeActive',
            'hotspotActive',
            'systemResource',
            'interfaces',
            'stats',
            'routers',
            'selectedRouter'
        ));
    }

    public function pppoeActive(Request $request)
    {
        $routerId = $request->input('router_id');
        $mikrotik = $this->getMikrotikService($routerId);
        $active = $mikrotik->getPPPoEActive();
        return response()->json($active);
    }

    public function hotspotActive(Request $request)
    {
        $routerId = $request->input('router_id');
        $mikrotik = $this->getMikrotikService($routerId);
        $active = $mikrotik->getHotspotActive();
        return response()->json($active);
    }

    public function disconnect(Request $request)
    {
        $username = $request->input('username');
        $type = $request->input('type', 'pppoe');
        $routerId = $request->input('router_id');

        $mikrotik = $this->getMikrotikService($routerId);

        if ($type === 'pppoe') {
            $result = $mikrotik->disconnectPPPoE($username);
        } else {
            // Implement hotspot disconnect if needed
            $result = false;
        }

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'User disconnected successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to disconnect user'
        ], 500);
    }

    public function systemResource(Request $request)
    {
        $routerId = $request->input('router_id');
        $mikrotik = $this->getMikrotikService($routerId);
        $resource = $mikrotik->getSystemResource();
        return response()->json($resource);
    }

    public function trafficStats(Request $request)
    {
        $interface = $request->input('interface', 'ether1');
        $routerId = $request->input('router_id');
        $mikrotik = $this->getMikrotikService($routerId);
        $stats = $mikrotik->getTrafficStats($interface);
        return response()->json($stats);
    }

    public function testConnection(Request $request)
    {
        $routerId = $request->input('router_id');
        $mikrotik = $this->getMikrotikService($routerId);
        $connected = $mikrotik->isConnected();

        if ($connected) {
            $resource = $mikrotik->getSystemResource();
            return response()->json([
                'success' => true,
                'message' => 'Connected to Mikrotik successfully',
                'data' => $resource
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to connect to Mikrotik'
        ], 500);
    }
}
