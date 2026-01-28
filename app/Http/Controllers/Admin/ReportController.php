<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Agent;
use App\Models\Collector;
use App\Models\VoucherPurchase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        $previousStart = $this->getPreviousStartDate($period, $startDate);

        // Summary Stats
        $totalRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('amount');

        $previousRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$previousStart, $startDate])
            ->sum('amount');

        $revenueGrowth = $previousRevenue > 0 
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) 
            : 0;

        $activeCustomers = Customer::where('status', 'active')->count();
        $previousActiveCustomers = Customer::where('status', 'active')
            ->where('created_at', '<', $startDate)
            ->count();
        $customerGrowth = $previousActiveCustomers > 0 
            ? round((($activeCustomers - $previousActiveCustomers) / $previousActiveCustomers) * 100, 1) 
            : 0;

        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->count();
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();

        $voucherSales = VoucherPurchase::whereBetween('created_at', [$startDate, $endDate])->count();
        $voucherRevenue = VoucherPurchase::whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // Chart Data - Revenue (last 6 months)
        $revenueLabels = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenueLabels[] = $month->format('M Y');
            $revenueData[] = Invoice::where('status', 'paid')
                ->whereYear('paid_date', $month->year)
                ->whereMonth('paid_date', $month->month)
                ->sum('amount');
        }

        // Chart Data - Customer Growth (last 6 months)
        $customerLabels = [];
        $customerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $customerLabels[] = $month->format('M Y');
            $customerData[] = Customer::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // Package Distribution
        $packages = Package::withCount('customers')->get();
        $packageLabels = $packages->pluck('name')->toArray();
        $packageData = $packages->pluck('customers_count')->toArray();

        // Payment Methods (simplified)
        $paymentLabels = ['Cash', 'Transfer', 'Online'];
        $paymentData = [
            Invoice::where('payment_method', 'cash')->where('status', 'paid')->count(),
            Invoice::where('payment_method', 'transfer')->where('status', 'paid')->count(),
            Invoice::whereIn('payment_method', ['midtrans', 'xendit'])->where('status', 'paid')->count(),
        ];

        // Invoice Status
        $invoiceStatusData = [
            Invoice::where('status', 'paid')->count(),
            Invoice::where('status', 'unpaid')->where('due_date', '>=', Carbon::now())->count(),
            Invoice::where('status', 'unpaid')->where('due_date', '<', Carbon::now())->count(),
        ];

        // Top Packages
        $topPackages = Package::withCount('customers')
            ->with(['customers.invoices' => function($q) use ($startDate, $endDate) {
                $q->where('status', 'paid')->whereBetween('paid_date', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($package) {
                $package->revenue = $package->customers->sum(function($customer) {
                    return $customer->invoices->sum('amount');
                });
                return $package;
            })
            ->sortByDesc('customers_count')
            ->take(5);

        // Top Collectors
        $topCollectors = Collector::withCount(['payments' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($collector) use ($startDate, $endDate) {
                $collector->total_collected = $collector->payments()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                $collector->collections_count = $collector->payments_count;
                return $collector;
            })
            ->sortByDesc('total_collected')
            ->take(5);

        // Agent Performance - simplified since voucher_purchases doesn't have agent_id
        $agentPerformance = Agent::all()->map(function($agent) {
            // Set default values since we don't have agent-specific voucher tracking
            $agent->vouchers_sold = 0;
            $agent->revenue = 0;
            $agent->commission = 0;
            
            return $agent;
        });

        return view('admin.reports.index', compact(
            'totalRevenue', 'revenueGrowth', 'activeCustomers', 'customerGrowth',
            'paidInvoices', 'totalInvoices', 'voucherSales', 'voucherRevenue',
            'revenueLabels', 'revenueData', 'customerLabels', 'customerData',
            'packageLabels', 'packageData', 'paymentLabels', 'paymentData',
            'invoiceStatusData', 'topPackages', 'topCollectors', 'agentPerformance'
        ));
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'summary');
        $period = $request->get('period', 'month');
        $format = $request->get('format', 'csv');
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        
        $filename = "report_{$type}_{$period}_" . date('Y-m-d') . ".{$format}";
        
        $headers = [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/json',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        if ($format === 'json') {
            return $this->exportJson($type, $startDate, $endDate);
        }

        $callback = function() use ($type, $period, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Laporan ISP Billing - ' . ucfirst($type) . ' (' . ucfirst($period) . ')']);
            fputcsv($file, ['Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($file, ['Period: ' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')]);
            fputcsv($file, []);
            
            switch ($type) {
                case 'revenue':
                    $this->exportRevenue($file, $startDate, $endDate);
                    break;
                case 'customers':
                    $this->exportCustomers($file, $startDate, $endDate);
                    break;
                case 'invoices':
                    $this->exportInvoices($file, $startDate, $endDate);
                    break;
                case 'packages':
                    $this->exportPackages($file);
                    break;
                case 'collectors':
                    $this->exportCollectors($file, $startDate, $endDate);
                    break;
                default:
                    $this->exportSummary($file, $startDate, $endDate);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportSummary($file, $startDate, $endDate)
    {
        fputcsv($file, ['RINGKASAN']);
        fputcsv($file, ['Metrik', 'Nilai']);
        fputcsv($file, ['Total Pelanggan Aktif', Customer::where('status', 'active')->count()]);
        fputcsv($file, ['Total Pelanggan Suspended', Customer::where('status', 'suspended')->count()]);
        fputcsv($file, ['Total Pendapatan Periode', 'Rp ' . number_format(Invoice::where('status', 'paid')->whereBetween('paid_date', [$startDate, $endDate])->sum('amount'), 0, ',', '.')]);
        fputcsv($file, ['Invoice Terbayar', Invoice::where('status', 'paid')->whereBetween('paid_date', [$startDate, $endDate])->count()]);
        fputcsv($file, ['Invoice Belum Bayar', Invoice::where('status', 'unpaid')->count()]);
        fputcsv($file, ['Invoice Overdue', Invoice::where('status', 'unpaid')->where('due_date', '<', Carbon::now())->count()]);
        fputcsv($file, []);
        
        // Packages
        fputcsv($file, ['DISTRIBUSI PAKET']);
        fputcsv($file, ['Nama Paket', 'Jumlah Pelanggan', 'Harga']);
        foreach (Package::withCount('customers')->get() as $package) {
            fputcsv($file, [$package->name, $package->customers_count, 'Rp ' . number_format($package->price, 0, ',', '.')]);
        }
    }

    private function exportRevenue($file, $startDate, $endDate)
    {
        fputcsv($file, ['LAPORAN PENDAPATAN']);
        fputcsv($file, ['Tanggal', 'Invoice', 'Pelanggan', 'Paket', 'Jumlah', 'Metode Bayar']);
        
        $invoices = Invoice::with(['customer', 'package'])
            ->where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->orderBy('paid_date', 'desc')
            ->get();
        
        $total = 0;
        foreach ($invoices as $inv) {
            fputcsv($file, [
                $inv->paid_date?->format('d/m/Y'),
                $inv->invoice_number,
                $inv->customer?->name ?? '-',
                $inv->package?->name ?? '-',
                $inv->amount,
                $inv->payment_method ?? 'cash'
            ]);
            $total += $inv->amount;
        }
        
        fputcsv($file, []);
        fputcsv($file, ['', '', '', 'TOTAL', $total, '']);
    }

    private function exportCustomers($file, $startDate, $endDate)
    {
        fputcsv($file, ['LAPORAN PELANGGAN']);
        fputcsv($file, ['ID', 'Nama', 'Telepon', 'Alamat', 'Paket', 'Status', 'Tgl Daftar']);
        
        $customers = Customer::with('package')
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($customers as $cust) {
            fputcsv($file, [
                $cust->id,
                $cust->name,
                $cust->phone,
                $cust->address,
                $cust->package?->name ?? '-',
                $cust->status,
                $cust->created_at?->format('d/m/Y')
            ]);
        }
        
        fputcsv($file, []);
        fputcsv($file, ['Total Pelanggan', $customers->count()]);
        fputcsv($file, ['Aktif', $customers->where('status', 'active')->count()]);
        fputcsv($file, ['Suspended', $customers->where('status', 'suspended')->count()]);
    }

    private function exportInvoices($file, $startDate, $endDate)
    {
        fputcsv($file, ['LAPORAN INVOICE']);
        fputcsv($file, ['No Invoice', 'Pelanggan', 'Paket', 'Jumlah', 'Jatuh Tempo', 'Status', 'Tgl Bayar']);
        
        $invoices = Invoice::with(['customer', 'package'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($invoices as $inv) {
            fputcsv($file, [
                $inv->invoice_number,
                $inv->customer?->name ?? '-',
                $inv->package?->name ?? '-',
                $inv->amount,
                $inv->due_date?->format('d/m/Y'),
                $inv->status,
                $inv->paid_date?->format('d/m/Y') ?? '-'
            ]);
        }
        
        fputcsv($file, []);
        fputcsv($file, ['Total Invoice', $invoices->count()]);
        fputcsv($file, ['Terbayar', $invoices->where('status', 'paid')->count()]);
        fputcsv($file, ['Belum Bayar', $invoices->where('status', 'unpaid')->count()]);
    }

    private function exportPackages($file)
    {
        fputcsv($file, ['LAPORAN PAKET']);
        fputcsv($file, ['Nama Paket', 'Bandwidth', 'Harga', 'Jumlah Pelanggan', 'Pendapatan Potensial']);
        
        $packages = Package::withCount('customers')->get();
        
        foreach ($packages as $pkg) {
            fputcsv($file, [
                $pkg->name,
                $pkg->bandwidth ?? '-',
                $pkg->price,
                $pkg->customers_count,
                $pkg->price * $pkg->customers_count
            ]);
        }
    }

    private function exportCollectors($file, $startDate, $endDate)
    {
        fputcsv($file, ['LAPORAN KOLEKTOR']);
        fputcsv($file, ['Nama', 'Telepon', 'Jumlah Tagihan', 'Total Terkumpul']);
        
        $collectors = Collector::all();
        
        foreach ($collectors as $col) {
            $collected = $col->payments()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');
            $count = $col->payments()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            
            fputcsv($file, [
                $col->name,
                $col->phone ?? '-',
                $count,
                $collected
            ]);
        }
    }

    private function exportJson($type, $startDate, $endDate)
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'type' => $type,
        ];

        switch ($type) {
            case 'revenue':
                $data['data'] = Invoice::with(['customer:id,name', 'package:id,name'])
                    ->where('status', 'paid')
                    ->whereBetween('paid_date', [$startDate, $endDate])
                    ->get(['id', 'invoice_number', 'customer_id', 'package_id', 'amount', 'paid_date', 'payment_method']);
                break;
            case 'customers':
                $data['data'] = Customer::with('package:id,name')
                    ->get(['id', 'name', 'phone', 'address', 'package_id', 'status', 'created_at']);
                break;
            case 'invoices':
                $data['data'] = Invoice::with(['customer:id,name', 'package:id,name'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(['id', 'invoice_number', 'customer_id', 'package_id', 'amount', 'due_date', 'status', 'paid_date']);
                break;
            default:
                $data['summary'] = [
                    'active_customers' => Customer::where('status', 'active')->count(),
                    'suspended_customers' => Customer::where('status', 'suspended')->count(),
                    'total_revenue' => Invoice::where('status', 'paid')->whereBetween('paid_date', [$startDate, $endDate])->sum('amount'),
                    'paid_invoices' => Invoice::where('status', 'paid')->whereBetween('paid_date', [$startDate, $endDate])->count(),
                    'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
                    'overdue_invoices' => Invoice::where('status', 'unpaid')->where('due_date', '<', Carbon::now())->count(),
                ];
        }

        return response()->json($data);
    }

    public function daily(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());
        $targetDate = Carbon::parse($date);

        $revenue = Invoice::where('status', 'paid')
            ->whereDate('paid_date', $targetDate)
            ->sum('amount');

        $invoicesPaid = Invoice::where('status', 'paid')
            ->whereDate('paid_date', $targetDate)
            ->count();

        $newCustomers = Customer::whereDate('created_at', $targetDate)->count();

        $payments = Invoice::with(['customer', 'package'])
            ->where('status', 'paid')
            ->whereDate('paid_date', $targetDate)
            ->orderBy('paid_date', 'desc')
            ->get();

        return view('admin.reports.daily', compact('date', 'revenue', 'invoicesPaid', 'newCustomers', 'payments'));
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Daily breakdown
        $dailyRevenue = [];
        for ($day = 1; $day <= $endDate->day; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            $dailyRevenue[] = [
                'date' => $date->format('d'),
                'revenue' => Invoice::where('status', 'paid')
                    ->whereDate('paid_date', $date)
                    ->sum('amount'),
                'count' => Invoice::where('status', 'paid')
                    ->whereDate('paid_date', $date)
                    ->count(),
            ];
        }

        $totalRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('amount');

        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->count();

        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        $churnedCustomers = Customer::where('status', 'suspended')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        return view('admin.reports.monthly', compact(
            'month', 'year', 'dailyRevenue', 'totalRevenue', 
            'totalInvoices', 'paidInvoices', 'newCustomers', 'churnedCustomers'
        ));
    }

    private function getStartDate($period)
    {
        return match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function getPreviousStartDate($period, $currentStart)
    {
        return match($period) {
            'today' => Carbon::yesterday(),
            'week' => $currentStart->copy()->subWeek(),
            'month' => $currentStart->copy()->subMonth(),
            'year' => $currentStart->copy()->subYear(),
            default => $currentStart->copy()->subMonth(),
        };
    }
}
