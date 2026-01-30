<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Collector;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomersExport
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');

        // Headers
        $headers = [
            'A1' => 'No',
            'B1' => 'Username',
            'C1' => 'PPPoE Username',
            'D1' => 'PPPoE Password',
            'E1' => 'Name',
            'F1' => 'Phone',
            'G1' => 'Email',
            'H1' => 'Address',
            'I1' => 'Package',
            'J1' => 'Static IP',
            'K1' => 'MAC Address',
            'L1' => 'Status',
            'M1' => 'Join Date',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        // Get customers data
        $query = Customer::with('package');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['package_id'])) {
            $query->where('package_id', $this->filters['package_id']);
        }

        $customers = $query->orderBy('name')->get();

        // Fill data
        $row = 2;
        foreach ($customers as $index => $customer) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $customer->username ?? '');
            $sheet->setCellValue('C' . $row, $customer->pppoe_username ?? '');
            $sheet->setCellValue('D' . $row, $customer->pppoe_password ?? '');
            $sheet->setCellValue('E' . $row, $customer->name);
            $sheet->setCellValue('F' . $row, $customer->phone ?? '');
            $sheet->setCellValue('G' . $row, $customer->email ?? '');
            $sheet->setCellValue('H' . $row, $customer->address ?? '');
            $sheet->setCellValue('I' . $row, $customer->package?->name ?? '');
            $sheet->setCellValue('J' . $row, $customer->static_ip ?? '');
            $sheet->setCellValue('K' . $row, $customer->mac_address ?? '');
            $sheet->setCellValue('L' . $row, $customer->status);
            $sheet->setCellValue('M' . $row, $customer->join_date?->format('Y-m-d') ?? '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add data borders
        if ($row > 2) {
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A2:M' . ($row - 1))->applyFromArray($dataStyle);
        }

        return $spreadsheet;
    }

    public function download($filename = 'customers.xlsx')
    {
        $spreadsheet = $this->export();
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Generate template for import
     */
    public static function template()
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Template
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = [
            'A1' => 'username',
            'B1' => 'pppoe_username',
            'C1' => 'pppoe_password',
            'D1' => 'name',
            'E1' => 'phone',
            'F1' => 'email',
            'G1' => 'address',
            'H1' => 'package_name',
            'I1' => 'static_ip',
            'J1' => 'mac_address',
            'K1' => 'collector_name',
            'L1' => 'status',
            'M1' => 'join_date',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        // Example data
        $sheet->setCellValue('A2', 'user001');
        $sheet->setCellValue('B2', 'pppoe001');
        $sheet->setCellValue('C2', 'password123');
        $sheet->setCellValue('D2', 'John Doe');
        $sheet->setCellValue('E2', '081234567890');
        $sheet->setCellValue('F2', 'john@example.com');
        $sheet->setCellValue('G2', 'Jl. Contoh No. 123');
        $sheet->setCellValue('H2', 'Paket 10 Mbps');
        $sheet->setCellValue('I2', '192.168.1.100');
        $sheet->setCellValue('J2', 'AA:BB:CC:DD:EE:FF');
        $sheet->setCellValue('K2', 'Nama Collector');
        $sheet->setCellValue('L2', 'active');
        $sheet->setCellValue('M2', '2024-01-15');

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Instructions
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');

        $instructions = [
            ['Petunjuk Pengisian Template Import Pelanggan'],
            [''],
            ['Kolom', 'Keterangan', 'Wajib'],
            ['username', 'Username login pelanggan (unik)', 'Tidak'],
            ['pppoe_username', 'Username PPPoE untuk koneksi internet', 'Tidak'],
            ['pppoe_password', 'Password PPPoE', 'Tidak'],
            ['name', 'Nama lengkap pelanggan', 'Ya'],
            ['phone', 'Nomor telepon pelanggan', 'Tidak'],
            ['email', 'Email pelanggan', 'Tidak'],
            ['address', 'Alamat pelanggan', 'Tidak'],
            ['package_name', 'Nama paket (harus sesuai dengan nama paket di sistem)', 'Tidak'],
            ['static_ip', 'IP statis yang diberikan ke pelanggan', 'Tidak'],
            ['mac_address', 'MAC Address perangkat pelanggan', 'Tidak'],
            ['collector_name', 'Nama kolektor yang bertanggung jawab (harus sesuai dengan nama kolektor di sistem)', 'Tidak'],
            ['status', 'Status: active, inactive, atau suspended (default: active)', 'Tidak'],
            ['join_date', 'Tanggal bergabung format YYYY-MM-DD (default: hari ini)', 'Tidak'],
            [''],
            ['Catatan:'],
            ['- Kolom name wajib diisi'],
            ['- Username harus unik, jika sudah ada akan di-skip'],
            ['- Nama paket harus sesuai dengan yang ada di sistem'],
            ['- Nama kolektor harus sesuai dengan yang ada di sistem'],
            ['- Status yang valid: active, inactive, suspended'],
        ];

        $row = 1;
        foreach ($instructions as $line) {
            if (is_array($line)) {
                $col = 'A';
                foreach ($line as $cell) {
                    $instructionSheet->setCellValue($col . $row, $cell);
                    $col++;
                }
            }
            $row++;
        }

        // Style instruction header
        $instructionSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
        ]);
        $instructionSheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        // Auto-size instruction columns
        $instructionSheet->getColumnDimension('A')->setWidth(20);
        $instructionSheet->getColumnDimension('B')->setWidth(50);
        $instructionSheet->getColumnDimension('C')->setWidth(10);

        // Sheet 3: Package List
        $packageSheet = $spreadsheet->createSheet();
        $packageSheet->setTitle('Daftar Paket');

        $packageSheet->setCellValue('A1', 'Nama Paket');
        $packageSheet->setCellValue('B1', 'Harga');
        $packageSheet->setCellValue('C1', 'Kecepatan');

        $packageSheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DBEAFE'],
            ],
        ]);

        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $row = 2;
        foreach ($packages as $package) {
            $packageSheet->setCellValue('A' . $row, $package->name);
            $packageSheet->setCellValue('B' . $row, 'Rp ' . number_format($package->price, 0, ',', '.'));
            $packageSheet->setCellValue('C' . $row, $package->speed ?? '-');
            $row++;
        }

        $packageSheet->getColumnDimension('A')->setAutoSize(true);
        $packageSheet->getColumnDimension('B')->setAutoSize(true);
        $packageSheet->getColumnDimension('C')->setAutoSize(true);

        // Sheet 4: Collector List
        $collectorSheet = $spreadsheet->createSheet();
        $collectorSheet->setTitle('Daftar Kolektor');

        $collectorSheet->setCellValue('A1', 'Nama Kolektor');
        $collectorSheet->setCellValue('B1', 'Area');
        $collectorSheet->setCellValue('C1', 'Status');

        $collectorSheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FDE68A'],
            ],
        ]);

        $collectors = Collector::where('status', 'active')->orderBy('name')->get();
        $row = 2;
        foreach ($collectors as $collector) {
            $collectorSheet->setCellValue('A' . $row, $collector->name);
            $collectorSheet->setCellValue('B' . $row, $collector->area ?? '-');
            $collectorSheet->setCellValue('C' . $row, $collector->status);
            $row++;
        }

        $collectorSheet->getColumnDimension('A')->setAutoSize(true);
        $collectorSheet->getColumnDimension('B')->setAutoSize(true);
        $collectorSheet->getColumnDimension('C')->setAutoSize(true);

        // Set active sheet back to template
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public static function downloadTemplate($filename = 'template_import_customers.xlsx')
    {
        $spreadsheet = self::template();
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
