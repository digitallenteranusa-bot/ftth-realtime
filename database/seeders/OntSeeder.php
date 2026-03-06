<?php
namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
use Illuminate\Database\Seeder;

class OntSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $odps = Odp::all();
        $ponPorts = PonPort::all();
        $statuses = ['online', 'online', 'online', 'online', 'offline', 'los', 'online', 'online'];

        foreach ($customers as $i => $customer) {
            $odp = $odps[$i % $odps->count()];
            $ponPort = $ponPorts[$i % $ponPorts->count()];

            Ont::create([
                'odp_id' => $odp->id,
                'customer_id' => $customer->id,
                'olt_id' => $ponPort->olt_id,
                'pon_port_id' => $ponPort->id,
                'name' => "ONT-{$customer->name}",
                'serial_number' => 'ZTEG' . strtoupper(substr(md5($i . 'ont'), 0, 8)),
                'ont_id_number' => $i + 1,
                'status' => $statuses[$i % count($statuses)],
                'rx_power' => round(rand(-2800, -1500) / 100, 2),
                'tx_power' => round(rand(200, 350) / 100, 2),
                'lat' => $customer->lat ?? ($odp->lat + 0.001 * ($i % 3)),
                'lng' => $customer->lng ?? ($odp->lng + 0.001 * ($i % 3)),
                'last_online_at' => now()->subMinutes(rand(0, 120)),
            ]);
        }
    }
}
