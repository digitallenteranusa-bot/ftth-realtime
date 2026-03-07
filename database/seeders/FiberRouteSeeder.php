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
    /**
     * Generate road-following waypoints between two points.
     * Creates L-shaped or stepped paths instead of diagonal lines.
     */
    private function roadPath(float $lat1, float $lng1, float $lat2, float $lng2, string $type = 'L'): array
    {
        if ($type === 'feeder') {
            // Feeder (OLT→ODC): go east/west first along main road, then turn north/south
            return [
                [$lat1, $lng1],
                [$lat1, $lng2],        // belok horizontal dulu (ikuti jalan raya)
                [$lat2, $lng2],        // lalu vertikal ke tujuan
            ];
        }

        if ($type === 'distribution') {
            // Distribution (ODC→ODP): stepped path with small offset to avoid overlap
            $midLat = $lat1 + ($lat2 - $lat1) * 0.4;
            $offset = 0.0003; // sedikit offset agar tidak tumpang tindih
            return [
                [$lat1, $lng1],
                [$midLat, $lng1 + $offset],  // jalan ke utara/selatan sedikit
                [$midLat, $lng2],             // belok ke timur/barat
                [$lat2, $lng2],               // lurus ke ODP
            ];
        }

        // Drop (ODP→ONT): simple L-turn, alternate between horizontal-first and vertical-first
        if ($type === 'drop_h') {
            return [
                [$lat1, $lng1],
                [$lat1, $lng2],    // horizontal dulu
                [$lat2, $lng2],    // lalu vertikal
            ];
        }

        // drop_v: vertical first
        return [
            [$lat1, $lng1],
            [$lat2, $lng1],    // vertikal dulu
            [$lat2, $lng2],    // lalu horizontal
        ];
    }

    public function run(): void
    {
        $olt = Olt::first();
        $odcs = Odc::all();

        // OLT to ODCs (feeder cable - ikuti jalan raya utama)
        foreach ($odcs as $odc) {
            FiberRoute::create([
                'name' => "Feeder {$olt->name} - {$odc->name}",
                'source_type' => 'olt',
                'source_id' => $olt->id,
                'destination_type' => 'odc',
                'destination_id' => $odc->id,
                'coordinates' => $this->roadPath($olt->lat, $olt->lng, $odc->lat, $odc->lng, 'feeder'),
                'color' => '#3388ff',
                'status' => 'active',
            ]);

            // ODC to ODPs (distribution cable - ikuti jalan distribusi)
            foreach ($odc->odps as $odp) {
                FiberRoute::create([
                    'name' => "Distribution {$odc->name} - {$odp->name}",
                    'source_type' => 'odc',
                    'source_id' => $odc->id,
                    'destination_type' => 'odp',
                    'destination_id' => $odp->id,
                    'coordinates' => $this->roadPath($odc->lat, $odc->lng, $odp->lat, $odp->lng, 'distribution'),
                    'color' => '#33cc33',
                    'status' => 'active',
                ]);

                // ODP to ONTs (drop cable - ikuti gang/jalan kecil ke rumah)
                foreach ($odp->onts as $j => $ont) {
                    $dropType = ($j % 2 === 0) ? 'drop_h' : 'drop_v';
                    FiberRoute::create([
                        'name' => "Drop {$odp->name} - {$ont->name}",
                        'source_type' => 'odp',
                        'source_id' => $odp->id,
                        'destination_type' => 'ont',
                        'destination_id' => $ont->id,
                        'coordinates' => $this->roadPath($odp->lat, $odp->lng, $ont->lat, $ont->lng, $dropType),
                        'color' => '#ff9933',
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}
