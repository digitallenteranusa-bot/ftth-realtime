<?php

namespace App\Console\Commands;

use App\Models\FiberRoute;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use Illuminate\Console\Command;

class GenerateFiberRoutes extends Command
{
    protected $signature = 'fiber:generate-routes';
    protected $description = 'Generate fiber routes for all existing devices that are missing routes';

    public function handle()
    {
        $created = 0;

        // OLT → ODC
        $odcs = Odc::whereNotNull('olt_id')->with('olt')->get();
        foreach ($odcs as $odc) {
            $exists = FiberRoute::where('source_type', 'olt')
                ->where('source_id', $odc->olt_id)
                ->where('destination_type', 'odc')
                ->where('destination_id', $odc->id)
                ->exists();

            if (!$exists && $odc->olt && $odc->olt->lat && $odc->olt->lng) {
                FiberRoute::create([
                    'name' => "Feeder {$odc->olt->name} - {$odc->name}",
                    'source_type' => 'olt',
                    'source_id' => $odc->olt_id,
                    'destination_type' => 'odc',
                    'destination_id' => $odc->id,
                    'coordinates' => [
                        [$odc->olt->lat, $odc->olt->lng],
                        [$odc->lat, $odc->lng],
                    ],
                    'color' => '#3388ff',
                    'status' => 'active',
                ]);
                $created++;
                $this->info("OLT→ODC: {$odc->olt->name} → {$odc->name}");
            }
        }

        // ODC → ODP
        $odps = Odp::whereNotNull('odc_id')->with('odc')->get();
        foreach ($odps as $odp) {
            $exists = FiberRoute::where('source_type', 'odc')
                ->where('source_id', $odp->odc_id)
                ->where('destination_type', 'odp')
                ->where('destination_id', $odp->id)
                ->exists();

            if (!$exists && $odp->odc) {
                FiberRoute::create([
                    'name' => "Distribusi {$odp->odc->name} - {$odp->name}",
                    'source_type' => 'odc',
                    'source_id' => $odp->odc_id,
                    'destination_type' => 'odp',
                    'destination_id' => $odp->id,
                    'coordinates' => [
                        [$odp->odc->lat, $odp->odc->lng],
                        [$odp->lat, $odp->lng],
                    ],
                    'color' => '#33cc33',
                    'status' => 'active',
                ]);
                $created++;
                $this->info("ODC→ODP: {$odp->odc->name} → {$odp->name}");
            }
        }

        // ODP → ONT
        $onts = Ont::whereNotNull('odp_id')->with('odp')->get();
        foreach ($onts as $ont) {
            $exists = FiberRoute::where('source_type', 'odp')
                ->where('source_id', $ont->odp_id)
                ->where('destination_type', 'ont')
                ->where('destination_id', $ont->id)
                ->exists();

            if (!$exists && $ont->odp && $ont->lat && $ont->lng) {
                $label = $ont->name ?: $ont->serial_number;
                FiberRoute::create([
                    'name' => "Drop {$ont->odp->name} - {$label}",
                    'source_type' => 'odp',
                    'source_id' => $ont->odp_id,
                    'destination_type' => 'ont',
                    'destination_id' => $ont->id,
                    'coordinates' => [
                        [$ont->odp->lat, $ont->odp->lng],
                        [$ont->lat, $ont->lng],
                    ],
                    'color' => '#ff9933',
                    'status' => 'active',
                ]);
                $created++;
                $this->info("ODP→ONT: {$ont->odp->name} → {$label}");
            }
        }

        $this->info("Done! Total routes created: {$created}");
    }
}
