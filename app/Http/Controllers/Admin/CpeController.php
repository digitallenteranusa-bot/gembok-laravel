<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GenieAcsService;
use Illuminate\Http\Request;

class CpeController extends Controller
{
    protected $genieacs;

    public function __construct(GenieAcsService $genieacs)
    {
        $this->genieacs = $genieacs;
    }

    public function index(Request $request)
    {
        $query = [];
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query = [
                '_id' => ['$regex' => $search]
            ];
        }

        $devices = $this->genieacs->getDevices($query);
        
        if ($devices === null) {
            return view('admin.cpe.index', [
                'connected' => false,
                'error' => 'Failed to connect to GenieACS. Please check your configuration.',
                'devices' => []
            ]);
        }

        // Process devices to add status
        $processedDevices = collect($devices)->map(function ($device) {
            $lastInform = $device['_lastInform'] ?? null;
            
            // Handle different lastInform formats (timestamp or ISO date string)
            $lastInformTimestamp = null;
            if ($lastInform) {
                if (is_numeric($lastInform)) {
                    $lastInformTimestamp = (int) $lastInform;
                } elseif (is_string($lastInform)) {
                    $lastInformTimestamp = strtotime($lastInform) * 1000;
                }
            }
            
            $now = time() * 1000;
            $isOnline = $lastInformTimestamp && ($now - $lastInformTimestamp) < 300000; // 5 minutes

            return [
                'id' => $device['_id'] ?? 'Unknown',
                'serial' => $this->getDeviceParam($device, 'InternetGatewayDevice.DeviceInfo.SerialNumber'),
                'model' => $this->getDeviceParam($device, 'InternetGatewayDevice.DeviceInfo.ModelName'),
                'manufacturer' => $this->getDeviceParam($device, 'InternetGatewayDevice.DeviceInfo.Manufacturer'),
                'ip_address' => $this->getDeviceParam($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress'),
                'status' => $isOnline ? 'online' : 'offline',
                'last_inform' => $lastInformTimestamp ? date('Y-m-d H:i:s', $lastInformTimestamp / 1000) : 'Never',
            ];
        });

        $stats = [
            'total' => $processedDevices->count(),
            'online' => $processedDevices->where('status', 'online')->count(),
            'offline' => $processedDevices->where('status', 'offline')->count(),
        ];

        return view('admin.cpe.index', [
            'connected' => true,
            'devices' => $processedDevices,
            'stats' => $stats
        ]);
    }

    public function show($deviceId)
    {
        $device = $this->genieacs->getDevice($deviceId);
        
        if (!$device) {
            abort(404, 'Device not found');
        }

        $status = $this->genieacs->getDeviceStatus($deviceId);
        $wifiInfo = $this->genieacs->getWiFiInfo($deviceId);

        return view('admin.cpe.show', compact('device', 'status', 'wifiInfo'));
    }

    public function reboot($deviceId)
    {
        $result = $this->genieacs->rebootDevice($deviceId);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Reboot command sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send reboot command'
        ], 500);
    }

    public function refresh($deviceId)
    {
        $result = $this->genieacs->refreshDevice($deviceId);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Refresh command sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send refresh command'
        ], 500);
    }

    public function factoryReset($deviceId)
    {
        $result = $this->genieacs->factoryReset($deviceId);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Factory reset command sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send factory reset command'
        ], 500);
    }

    public function updateWifi(Request $request, $deviceId)
    {
        $validated = $request->validate([
            'ssid' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:8|max:63',
            'channel' => 'nullable|integer|min:1|max:13',
            'enabled' => 'nullable|boolean',
        ]);

        $result = $this->genieacs->updateWiFiSettings($deviceId, $validated);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'WiFi settings updated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update WiFi settings'
        ], 500);
    }

    public function bulkReboot(Request $request)
    {
        $deviceIds = $request->input('device_ids', []);
        
        if (empty($deviceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No devices selected'
            ], 400);
        }

        $results = $this->genieacs->bulkReboot($deviceIds);
        
        return response()->json([
            'success' => true,
            'message' => 'Bulk reboot command sent',
            'results' => $results
        ]);
    }

    public function bulkRefresh(Request $request)
    {
        $deviceIds = $request->input('device_ids', []);
        
        if (empty($deviceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No devices selected'
            ], 400);
        }

        $results = $this->genieacs->bulkRefresh($deviceIds);
        
        return response()->json([
            'success' => true,
            'message' => 'Bulk refresh command sent',
            'results' => $results
        ]);
    }

    protected function getDeviceParam($device, $path)
    {
        $keys = explode('.', $path);
        $value = $device;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        // Extract _value if exists, otherwise return null for arrays
        if (is_array($value)) {
            if (isset($value['_value'])) {
                $value = $value['_value'];
            } else {
                return null;
            }
        }

        // Ensure we return a string or null
        return is_scalar($value) ? (string) $value : null;
    }
}
