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
            ['name' => 'ODC-Darmo-01', 'lat' => -7.2620, 'lng' => 112.7400, 'address' => 'Jl. Raya Darmo', 'capacity' => 96, 'splitter_ratio' => '1:8'],
            ['name' => 'ODC-Gubeng-01', 'lat' => -7.2680, 'lng' => 112.7540, 'address' => 'Jl. Gubeng', 'capacity' => 96, 'splitter_ratio' => '1:8'],
            ['name' => 'ODC-Pemuda-01', 'lat' => -7.2640, 'lng' => 112.7480, 'address' => 'Jl. Pemuda', 'capacity' => 144, 'splitter_ratio' => '1:8'],
        ];

        foreach ($odcData as $od) {
            $odc = Odc::create(array_merge($od, ['is_active' => true, 'used_ports' => rand(10, 40)]));

            // 4 ODPs per ODC
            for ($i = 1; $i <= 4; $i++) {
                Odp::create([
                    'name' => "{$odc->name}-ODP-{$i}",
                    'odc_id' => $odc->id,
                    'lat' => $odc->lat + (0.003 * $i),
                    'lng' => $odc->lng + (0.002 * ($i % 3)),
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
