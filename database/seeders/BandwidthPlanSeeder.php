<?php
namespace Database\Seeders;

use App\Models\BandwidthPlan;
use Illuminate\Database\Seeder;

class BandwidthPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Paket 10 Mbps', 'upload_speed' => 5000, 'download_speed' => 10000, 'price' => 150000],
            ['name' => 'Paket 20 Mbps', 'upload_speed' => 10000, 'download_speed' => 20000, 'price' => 250000],
            ['name' => 'Paket 50 Mbps', 'upload_speed' => 25000, 'download_speed' => 50000, 'price' => 400000],
            ['name' => 'Paket 100 Mbps', 'upload_speed' => 50000, 'download_speed' => 100000, 'price' => 600000],
        ];

        foreach ($plans as $plan) {
            BandwidthPlan::create(array_merge($plan, ['is_active' => true]));
        }
    }
}
