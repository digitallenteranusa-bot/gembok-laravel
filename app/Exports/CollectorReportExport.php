<?php

namespace App\Exports;

use App\Models\Collector;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CollectorReportExport
{
    protected $collector;
    protected $startDate;
    protected $endDate;

    public function __construct(Collector $collector, $startDate = null, $endDate = null)
    {
        $this->collector = $collector;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Collector');

        // ============ HEADER SECTION ============
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'LAPORAN COLLECTOR: ' . strtoupper($this->collector->name));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Info periode
        $periode = 'Semua Periode';
        if ($this->startDate && $this->endDate) {
            $periode = date('d/m/Y', strtotime($this->startDate)) . ' - ' . date('d/m/Y', strtotime($this->endDate));
        } elseif ($this->startDate) {
            $periode = 'Dari ' . date('d/m/Y', strtotime($this->startDate));
        } elseif ($this->endDate) {
            $periode = 'Sampai ' . date('d/m/Y', strtotime($this->endDate));
        }

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Periode: ' . $periode);
        $sheet->getStyle('A2')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', 'Tanggal Export: ' . date('d/m/Y H:i:s'));
        $sheet->getStyle('A3')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font' => ['italic' => true, 'size' => 10],
        ]);

        // ============ RINGKASAN SECTION ============
        $sheet->setCellValue('A5', 'RINGKASAN');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        // Get summary data
        $customers = Customer::where('collector_id', $this->collector->id)->get();
        $totalCustomers = $customers->count();
        $totalDebt = $customers->sum('total_debt');

        // Get payments
        $paymentsQuery = Payment::where('collector_id', $this->collector->id);
        if ($this->startDate) {
            $paymentsQuery->where('paid_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $paymentsQuery->where('paid_at', '<=', $this->endDate . ' 23:59:59');
        }
        $totalCollection = $paymentsQuery->sum('amount');
        $totalPayments = $paymentsQuery->count();

        $sheet->setCellValue('A6', 'Total Pelanggan');
        $sheet->setCellValue('B6', $totalCustomers);
        $sheet->setCellValue('A7', 'Total Hutang Pelanggan');
        $sheet->setCellValue('B7', $totalDebt);
        $sheet->setCellValue('A8', 'Total Pembayaran Terkumpul');
        $sheet->setCellValue('B8', $totalCollection);
        $sheet->setCellValue('A9', 'Jumlah Transaksi');
        $sheet->setCellValue('B9', $totalPayments);

        $sheet->getStyle('B7:B8')->getNumberFormat()->setFormatCode('#,##0');

        // ============ DETAIL PELANGGAN SECTION ============
        $sheet->setCellValue('A11', 'DETAIL PELANGGAN & HUTANG');
        $sheet->getStyle('A11')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        // Headers
        $headers = [
            'A12' => 'No',
            'B12' => 'Nama Pelanggan',
            'C12' => 'Username',
            'D12' => 'Telepon',
            'E12' => 'Paket',
            'F12' => 'Status',
            'G12' => 'Total Hutang',
            'H12' => 'Invoice Belum Bayar',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A12:H12')->applyFromArray($headerStyle);

        // Fill customer data
        $row = 13;
        $customers = Customer::where('collector_id', $this->collector->id)
            ->with(['package', 'invoices' => function($q) {
                $q->where('status', 'unpaid');
            }])
            ->orderBy('name')
            ->get();

        foreach ($customers as $index => $customer) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $customer->name);
            $sheet->setCellValue('C' . $row, $customer->username ?? '-');
            $sheet->setCellValue('D' . $row, $customer->phone ?? '-');
            $sheet->setCellValue('E' . $row, $customer->package?->name ?? '-');
            $sheet->setCellValue('F' . $row, ucfirst($customer->status));
            $sheet->setCellValue('G' . $row, $customer->total_debt ?? 0);
            $sheet->setCellValue('H' . $row, $customer->invoices->count());

            // Color coding for status
            $statusColor = match($customer->status) {
                'active' => '10B981',
                'suspended' => 'EF4444',
                'inactive' => '6B7280',
                default => 'FFFFFF',
            };
            $sheet->getStyle('F' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $statusColor],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ]);

            $row++;
        }

        $lastCustomerRow = $row - 1;

        // Format currency
        if ($lastCustomerRow >= 13) {
            $sheet->getStyle('G13:G' . $lastCustomerRow)->getNumberFormat()->setFormatCode('#,##0');

            // Add borders
            $sheet->getStyle('A13:H' . $lastCustomerRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ]);
        }

        // Total row
        $row++;
        $sheet->setCellValue('F' . $row, 'TOTAL:');
        $sheet->setCellValue('G' . $row, $totalDebt);
        $sheet->getStyle('F' . $row . ':G' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
        ]);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');

        // ============ DETAIL PEMBAYARAN SECTION ============
        $row += 3;
        $sheet->setCellValue('A' . $row, 'DETAIL PEMBAYARAN');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        $row++;
        $paymentHeaders = [
            'A' . $row => 'No',
            'B' . $row => 'Tanggal',
            'C' . $row => 'Pelanggan',
            'D' . $row => 'No. Invoice',
            'E' . $row => 'Metode Bayar',
            'F' . $row => 'Jumlah',
            'G' . $row => 'Referensi',
            'H' . $row => 'Catatan',
        ];

        foreach ($paymentHeaders as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($headerStyle);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->getStartColor()->setRGB('059669');

        // Get payments with details
        $paymentsQuery = Payment::where('collector_id', $this->collector->id)
            ->with(['invoice.customer']);
        if ($this->startDate) {
            $paymentsQuery->where('paid_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $paymentsQuery->where('paid_at', '<=', $this->endDate . ' 23:59:59');
        }
        $payments = $paymentsQuery->orderBy('paid_at', 'desc')->get();

        $row++;
        $paymentStartRow = $row;
        $totalPaymentAmount = 0;

        foreach ($payments as $index => $payment) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('C' . $row, $payment->invoice?->customer?->name ?? '-');
            $sheet->setCellValue('D' . $row, $payment->invoice?->invoice_number ?? '-');
            $sheet->setCellValue('E' . $row, $payment->method_label ?? ucfirst($payment->payment_method));
            $sheet->setCellValue('F' . $row, $payment->amount);
            $sheet->setCellValue('G' . $row, $payment->reference_number ?? '-');
            $sheet->setCellValue('H' . $row, $payment->notes ?? '-');

            $totalPaymentAmount += $payment->amount;
            $row++;
        }

        $lastPaymentRow = $row - 1;

        // Format and borders for payments
        if ($lastPaymentRow >= $paymentStartRow) {
            $sheet->getStyle('F' . $paymentStartRow . ':F' . $lastPaymentRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $paymentStartRow . ':H' . $lastPaymentRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ]);
        }

        // Total payment row
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, $totalPaymentAmount);
        $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1FAE5'],
            ],
        ]);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function download($filename = null)
    {
        if (!$filename) {
            $filename = 'laporan_collector_' . str_replace(' ', '_', strtolower($this->collector->name)) . '_' . date('Y-m-d_His') . '.xlsx';
        }

        $spreadsheet = $this->export();
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
