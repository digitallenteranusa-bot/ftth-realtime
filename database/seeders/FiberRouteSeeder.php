<?php
namespace Database\Seeders;

use App\Models\FiberRoute;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use Illuminate\Database\Seeder;

class FiberRouteSeeder extends Seeder
{
    public function run(): void
    {
        $olt = Olt::first();
        $odcs = Odc::all();

        // OLT to ODCs
        foreach ($odcs as $odc) {
            FiberRoute::create([
                'name' => "Feeder {$olt->name} - {$odc->name}",
                'source_type' => 'olt',
                'source_id' => $olt->id,
                'destination_type' => 'odc',
                'destination_id' => $odc->id,
                'coordinates' => [
                    [$olt->lat, $olt->lng],
                    [($olt->lat + $odc->lat) / 2, ($olt->lng + $odc->lng) / 2 + 0.002],
                    [$odc->lat, $odc->lng],
                ],
                'color' => '#3388ff',
                'status' => 'active',
            ]);

            // ODC to ODPs
            foreach ($odc->odps as $odp) {
                FiberRoute::create([
                    'name' => "Distribution {$odc->name} - {$odp->name}",
                    'source_type' => 'odc',
                    'source_id' => $odc->id,
                    'destination_type' => 'odp',
                    'destination_id' => $odp->id,
                    'coordinates' => [
                        [$odc->lat, $odc->lng],
                        [$odp->lat, $odp->lng],
                    ],
                    'color' => '#33cc33',
                    'status' => 'active',
                ]);

                // ODP to ONTs (last mile to customer)
                foreach ($odp->onts as $ont) {
                    FiberRoute::create([
                        'name' => "Drop {$odp->name} - {$ont->name}",
                        'source_type' => 'odp',
                        'source_id' => $odp->id,
                        'destination_type' => 'ont',
                        'destination_id' => $ont->id,
                        'coordinates' => [
                            [$odp->lat, $odp->lng],
                            [$ont->lat, $ont->lng],
                        ],
                        'color' => '#ff9933',
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}
