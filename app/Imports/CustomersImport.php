<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Collector;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomersImport
{
    protected $results = [
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    protected $packages = [];
    protected $collectors = [];

    public function __construct()
    {
        // Cache packages for lookup
        $this->packages = Package::pluck('id', 'name')->toArray();
        // Cache collectors for lookup
        $this->collectors = Collector::pluck('id', 'name')->toArray();
    }

    public function import($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            $header = array_shift($rows);
            $headerMap = $this->mapHeaders($header);

            if (!isset($headerMap['name'])) {
                $this->results['errors'][] = 'Kolom "name" tidak ditemukan di file. Kolom name wajib ada.';
                return $this->results;
            }

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because index starts at 0 and we skipped header

                try {
                    $data = $this->mapRowToData($row, $headerMap);

                    // Skip empty rows
                    if (empty($data['name'])) {
                        continue;
                    }

                    // Validate data
                    $validator = $this->validateData($data, $rowNumber);

                    if ($validator->fails()) {
                        $this->results['failed']++;
                        $this->results['errors'][] = "Baris {$rowNumber}: " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    // Check for duplicate username
                    if (!empty($data['username'])) {
                        $exists = Customer::where('username', $data['username'])->exists();
                        if ($exists) {
                            $this->results['skipped']++;
                            $this->results['errors'][] = "Baris {$rowNumber}: Username '{$data['username']}' sudah terdaftar, di-skip.";
                            continue;
                        }
                    }

                    // Map package name to ID
                    $packageId = null;
                    if (!empty($data['package_name'])) {
                        $packageId = $this->packages[$data['package_name']] ?? null;
                        if (!$packageId) {
                            $this->results['errors'][] = "Baris {$rowNumber}: Paket '{$data['package_name']}' tidak ditemukan, customer tetap dibuat tanpa paket.";
                        }
                    }

                    // Map collector name to ID
                    $collectorId = null;
                    if (!empty($data['collector_name'])) {
                        $collectorId = $this->collectors[$data['collector_name']] ?? null;
                        if (!$collectorId) {
                            $this->results['errors'][] = "Baris {$rowNumber}: Kolektor '{$data['collector_name']}' tidak ditemukan, customer tetap dibuat tanpa kolektor.";
                        }
                    }

                    // Create customer
                    Customer::create([
                        'username' => $data['username'] ?: null,
                        'pppoe_username' => $data['pppoe_username'] ?: null,
                        'pppoe_password' => $data['pppoe_password'] ?: null,
                        'name' => $data['name'],
                        'phone' => $data['phone'] ?: null,
                        'email' => $data['email'] ?: null,
                        'address' => $data['address'] ?: null,
                        'package_id' => $packageId,
                        'collector_id' => $collectorId,
                        'static_ip' => $data['static_ip'] ?: null,
                        'mac_address' => $data['mac_address'] ?: null,
                        'status' => $this->normalizeStatus($data['status']),
                        'join_date' => $this->parseDate($data['join_date']),
                    ]);

                    $this->results['success']++;

                } catch (\Exception $e) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->results['errors'][] = 'Error membaca file: ' . $e->getMessage();
        }

        return $this->results;
    }

    protected function mapHeaders($header)
    {
        $map = [];
        $expectedHeaders = [
            'username', 'pppoe_username', 'pppoe_password', 'name',
            'phone', 'email', 'address', 'package_name', 'static_ip',
            'mac_address', 'collector_name', 'status', 'join_date'
        ];

        foreach ($header as $index => $col) {
            $normalized = strtolower(trim($col ?? ''));
            $normalized = str_replace([' ', '-'], '_', $normalized);

            // Map common variations
            $variations = [
                'nama' => 'name',
                'nama_pelanggan' => 'name',
                'customer_name' => 'name',
                'telepon' => 'phone',
                'no_telepon' => 'phone',
                'no_hp' => 'phone',
                'handphone' => 'phone',
                'alamat' => 'address',
                'paket' => 'package_name',
                'nama_paket' => 'package_name',
                'package' => 'package_name',
                'ip' => 'static_ip',
                'ip_address' => 'static_ip',
                'mac' => 'mac_address',
                'tanggal_bergabung' => 'join_date',
                'tanggal_daftar' => 'join_date',
                'tgl_bergabung' => 'join_date',
                'tgl_daftar' => 'join_date',
                'collector' => 'collector_name',
                'kolektor' => 'collector_name',
                'nama_kolektor' => 'collector_name',
            ];

            if (isset($variations[$normalized])) {
                $normalized = $variations[$normalized];
            }

            if (in_array($normalized, $expectedHeaders)) {
                $map[$normalized] = $index;
            }
        }

        return $map;
    }

    protected function mapRowToData($row, $headerMap)
    {
        $data = [];
        $fields = [
            'username', 'pppoe_username', 'pppoe_password', 'name',
            'phone', 'email', 'address', 'package_name', 'static_ip',
            'mac_address', 'collector_name', 'status', 'join_date'
        ];

        foreach ($fields as $field) {
            $data[$field] = isset($headerMap[$field]) ? trim($row[$headerMap[$field]] ?? '') : '';
        }

        return $data;
    }

    protected function validateData($data, $rowNumber)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string',
        ];

        $messages = [
            'name.required' => 'Nama wajib diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'email.email' => 'Format email tidak valid',
            'phone.max' => 'Nomor telepon maksimal 20 karakter',
        ];

        return Validator::make($data, $rules, $messages);
    }

    protected function normalizeStatus($status)
    {
        $status = strtolower(trim($status));

        $validStatuses = ['active', 'inactive', 'suspended'];

        if (in_array($status, $validStatuses)) {
            return $status;
        }

        // Map common variations
        $statusMap = [
            'aktif' => 'active',
            'nonaktif' => 'inactive',
            'non-aktif' => 'inactive',
            'tidak_aktif' => 'inactive',
            'suspend' => 'suspended',
            'ditangguhkan' => 'suspended',
        ];

        return $statusMap[$status] ?? 'active';
    }

    protected function parseDate($dateString)
    {
        if (empty($dateString)) {
            return now();
        }

        try {
            // Try various date formats
            $formats = [
                'Y-m-d',
                'd-m-Y',
                'd/m/Y',
                'Y/m/d',
                'd M Y',
                'd F Y',
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // If it's a numeric value (Excel date serial)
            if (is_numeric($dateString)) {
                $unixDate = ($dateString - 25569) * 86400;
                return date('Y-m-d', $unixDate);
            }

            return now();
        } catch (\Exception $e) {
            return now();
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
