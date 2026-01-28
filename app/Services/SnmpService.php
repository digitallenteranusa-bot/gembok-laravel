<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SnmpService
{
    protected $enabled;
    protected $community;
    protected $version;
    protected $timeout;
    protected $retries;

    // Common OIDs
    const OID_SYSTEM_DESCR = '1.3.6.1.2.1.1.1.0';
    const OID_SYSTEM_UPTIME = '1.3.6.1.2.1.1.3.0';
    const OID_SYSTEM_NAME = '1.3.6.1.2.1.1.5.0';
    const OID_IF_NUMBER = '1.3.6.1.2.1.2.1.0';
    const OID_IF_DESCR = '1.3.6.1.2.1.2.2.1.2';
    const OID_IF_SPEED = '1.3.6.1.2.1.2.2.1.5';
    const OID_IF_IN_OCTETS = '1.3.6.1.2.1.2.2.1.10';
    const OID_IF_OUT_OCTETS = '1.3.6.1.2.1.2.2.1.16';
    const OID_IF_OPER_STATUS = '1.3.6.1.2.1.2.2.1.8';
    const OID_CPU_LOAD = '1.3.6.1.4.1.2021.11.11.0';
    const OID_MEMORY_TOTAL = '1.3.6.1.4.1.2021.4.5.0';
    const OID_MEMORY_FREE = '1.3.6.1.4.1.2021.4.6.0';

    public function __construct()
    {
        $this->enabled = config('services.snmp.enabled', false);
        $this->community = config('services.snmp.community', 'public');
        $this->version = config('services.snmp.version', '2c');
        $this->timeout = config('services.snmp.timeout', 5) * 1000000;
        $this->retries = config('services.snmp.retries', 2);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && function_exists('snmpget');
    }

    public function getSystemInfo(string $host): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'SNMP not enabled or extension not loaded'];
        }

        try {
            return [
                'description' => $this->get($host, self::OID_SYSTEM_DESCR),
                'uptime' => $this->formatUptime($this->get($host, self::OID_SYSTEM_UPTIME)),
                'name' => $this->get($host, self::OID_SYSTEM_NAME),
            ];
        } catch (\Exception $e) {
            Log::error('SNMP getSystemInfo failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function getInterfaces(string $host): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            $interfaces = [];
            $ifCount = (int) $this->get($host, self::OID_IF_NUMBER);

            for ($i = 1; $i <= $ifCount; $i++) {
                $interfaces[] = [
                    'index' => $i,
                    'name' => $this->get($host, self::OID_IF_DESCR . '.' . $i),
                    'speed' => $this->formatSpeed($this->get($host, self::OID_IF_SPEED . '.' . $i)),
                    'status' => $this->get($host, self::OID_IF_OPER_STATUS . '.' . $i) == 1 ? 'up' : 'down',
                    'in_octets' => (int) $this->get($host, self::OID_IF_IN_OCTETS . '.' . $i),
                    'out_octets' => (int) $this->get($host, self::OID_IF_OUT_OCTETS . '.' . $i),
                ];
            }

            return $interfaces;
        } catch (\Exception $e) {
            Log::error('SNMP getInterfaces failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getTrafficStats(string $host, int $ifIndex = 1): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $cacheKey = "snmp_traffic_{$host}_{$ifIndex}";
        $previous = Cache::get($cacheKey);
        $current = [
            'timestamp' => time(),
            'in' => (int) $this->get($host, self::OID_IF_IN_OCTETS . '.' . $ifIndex),
            'out' => (int) $this->get($host, self::OID_IF_OUT_OCTETS . '.' . $ifIndex),
        ];

        Cache::put($cacheKey, $current, 300);

        if (!$previous) {
            return ['in_bps' => 0, 'out_bps' => 0, 'message' => 'First sample collected'];
        }

        $timeDiff = $current['timestamp'] - $previous['timestamp'];
        if ($timeDiff <= 0) $timeDiff = 1;

        return [
            'in_bps' => round(($current['in'] - $previous['in']) * 8 / $timeDiff),
            'out_bps' => round(($current['out'] - $previous['out']) * 8 / $timeDiff),
            'in_bytes' => $current['in'] - $previous['in'],
            'out_bytes' => $current['out'] - $previous['out'],
            'interval' => $timeDiff,
        ];
    }

    public function getResourceUsage(string $host): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            $cpuIdle = (int) $this->get($host, self::OID_CPU_LOAD);
            $memTotal = (int) $this->get($host, self::OID_MEMORY_TOTAL);
            $memFree = (int) $this->get($host, self::OID_MEMORY_FREE);

            return [
                'cpu_usage' => 100 - $cpuIdle,
                'memory_total' => $memTotal,
                'memory_free' => $memFree,
                'memory_used' => $memTotal - $memFree,
                'memory_percent' => $memTotal > 0 ? round(($memTotal - $memFree) / $memTotal * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('SNMP getResourceUsage failed: ' . $e->getMessage());
            return [];
        }
    }

    public function ping(string $host): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $result = $this->get($host, self::OID_SYSTEM_UPTIME);
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function walkTable(string $host, string $oid): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
            $result = @snmpwalk($host, $this->community, $oid, $this->timeout, $this->retries);
            return $result ?: [];
        } catch (\Exception $e) {
            Log::error('SNMP walk failed: ' . $e->getMessage());
            return [];
        }
    }

    protected function get(string $host, string $oid): string
    {
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        $result = @snmpget($host, $this->community, $oid, $this->timeout, $this->retries);
        
        if ($result === false) {
            throw new \Exception("Failed to get OID {$oid} from {$host}");
        }

        // Clean up result
        $result = preg_replace('/^[^:]+:\s*/', '', $result);
        $result = trim($result, '"');
        
        return $result;
    }

    protected function formatUptime(string $ticks): string
    {
        $seconds = (int) $ticks / 100;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }

    protected function formatSpeed(string $bps): string
    {
        $bps = (int) $bps;
        if ($bps >= 1000000000) {
            return round($bps / 1000000000, 1) . ' Gbps';
        } elseif ($bps >= 1000000) {
            return round($bps / 1000000, 1) . ' Mbps';
        } elseif ($bps >= 1000) {
            return round($bps / 1000, 1) . ' Kbps';
        }
        return $bps . ' bps';
    }
}
