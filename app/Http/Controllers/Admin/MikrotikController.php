<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class MikrotikController extends Controller
{
    protected $mikrotik;

    public function __construct(MikrotikService $mikrotik)
    {
        $this->mikrotik = $mikrotik;
    }

    public function index()
    {
        $connected = $this->mikrotik->isConnected();
        
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
            ]);
        }

        $pppoeActive = $this->mikrotik->getPPPoEActive();
        $hotspotActive = $this->mikrotik->getHotspotActive();
        $systemResource = $this->mikrotik->getSystemResource();
        $interfaces = $this->mikrotik->getInterfaces();

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
            'stats'
        ));
    }

    public function pppoeActive()
    {
        $active = $this->mikrotik->getPPPoEActive();
        return response()->json($active);
    }

    public function hotspotActive()
    {
        $active = $this->mikrotik->getHotspotActive();
        return response()->json($active);
    }

    public function disconnect(Request $request)
    {
        $username = $request->input('username');
        $type = $request->input('type', 'pppoe');

        if ($type === 'pppoe') {
            $result = $this->mikrotik->disconnectPPPoE($username);
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

    public function systemResource()
    {
        $resource = $this->mikrotik->getSystemResource();
        return response()->json($resource);
    }

    public function trafficStats(Request $request)
    {
        $interface = $request->input('interface', 'ether1');
        $stats = $this->mikrotik->getTrafficStats($interface);
        return response()->json($stats);
    }

    public function testConnection()
    {
        $connected = $this->mikrotik->isConnected();
        
        if ($connected) {
            $resource = $this->mikrotik->getSystemResource();
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
