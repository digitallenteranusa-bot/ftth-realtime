<?php
namespace Database\Seeders;

use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Database\Seeder;

class OdcOdpSeeder extends Seeder
{
    public function run(): void
    {
        $odcData = [
            ['name' => 'ODC-Kelutan-01', 'lat' => -8.0520, 'lng' => 111.7090, 'address' => 'Jl. Raya Kelutan, Trenggalek', 'capacity' => 96, 'splitter_ratio' => '1:8'],
            ['name' => 'ODC-Ngantru-01', 'lat' => -8.0460, 'lng' => 111.7040, 'address' => 'Jl. Raya Ngantru, Trenggalek', 'capacity' => 96, 'splitter_ratio' => '1:8'],
            ['name' => 'ODC-Semarum-01', 'lat' => -8.0540, 'lng' => 111.7000, 'address' => 'Jl. Raya Semarum, Trenggalek', 'capacity' => 144, 'splitter_ratio' => '1:8'],
        ];

        foreach ($odcData as $od) {
            $odc = Odc::create(array_merge($od, ['is_active' => true, 'used_ports' => rand(10, 40)]));

            for ($i = 1; $i <= 4; $i++) {
                Odp::create([
                    'name' => "{$odc->name}-ODP-{$i}",
                    'odc_id' => $odc->id,
                    'lat' => $odc->lat + (0.002 * $i),
                    'lng' => $odc->lng + (0.0015 * ($i % 3)),
                    'address' => "Area {$i} - {$odc->name}",
                    'capacity' => 8,
                    'used_ports' => rand(2, 7),
                    'splitter_ratio' => '1:8',
                    'is_active' => true,
                ]);
            }
        }
    }
}
