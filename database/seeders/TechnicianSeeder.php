<?php

namespace Database\Seeders;

use App\Models\Technician;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = [
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'budi.tech@gembok.com',
                'role' => 'supervisor',
                'area_coverage' => 'Jakarta Utara',
                'is_active' => true,
                'join_date' => now()->subYears(2),
            ],
            [
                'name' => 'Asep Hidayat',
                'phone' => '081234567891',
                'email' => 'asep.tech@gembok.com',
                'role' => 'technician',
                'area_coverage' => 'Jakarta Barat',
                'is_active' => true,
                'join_date' => now()->subYear(1),
            ],
            [
                'name' => 'Dedi Kurniawan',
                'phone' => '081234567892',
                'email' => 'dedi.install@gembok.com',
                'role' => 'installer',
                'area_coverage' => 'Jakarta Pusat',
                'is_active' => true,
                'join_date' => now()->subMonths(6),
            ],
        ];

        foreach ($technicians as $tech) {
            Technician::create($tech);
        }
    }
}
