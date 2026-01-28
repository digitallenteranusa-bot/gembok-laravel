<?php

namespace Database\Seeders;

use App\Models\Collector;
use Illuminate\Database\Seeder;

class CollectorSeeder extends Seeder
{
    public function run(): void
    {
        $collectors = [
            [
                'name' => 'Siti Aminah',
                'phone' => '082134567890',
                'email' => 'siti.coll@gembok.com',
                'commission_rate' => 5.0,
                'status' => 'active',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Rudi Hartono',
                'phone' => '082134567891',
                'email' => 'rudi.coll@gembok.com',
                'commission_rate' => 7.5,
                'status' => 'active',
                'password' => bcrypt('password'),
            ],
        ];

        foreach ($collectors as $coll) {
            Collector::create($coll);
        }
    }
}
