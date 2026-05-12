<?php
namespace App\Jobs;

use App\Events\TrafficUpdated;
use App\Models\InterfaceTraffic;
use App\Models\Mikrotik;
use App\Models\PppoeSession;
use App\Services\Mikrotik\MikrotikApiService;
use App\Services\Mikrotik\MikrotikConnectionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollMikrotikJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MikrotikApiService $apiService): void
    {
        $mikrotiks = Mikrotik::where('is_active', true)->get();

        foreach ($mikrotiks as $mikrotik) {
            try {
                // Sync PPPoE sessions
                $sessions = $apiService->getActivePppoe($mikrotik);
                PppoeSession::where('mikrotik_id', $mikrotik->id)->update(['is_active' => false]);

                foreach ($sessions as $session) {
                    PppoeSession::updateOrCreate(
                        ['mikrotik_id' => $mikrotik->id, 'username' => $session['name'] ?? ''],
                        [
                            'caller_id' => $session['caller-id'] ?? null,
                            'service' => $session['service'] ?? null,
                            'uptime' => $session['uptime'] ?? null,
                            'ip_address' => $session['address'] ?? null,
                            'is_active' => true,
                        ]
                    );
                }

                // Sync interface traffic
                $interfaces = $apiService->getInterfaces($mikrotik);
                $trafficData = [];
                $topInterface = null;
                $maxBytes = 0;

                foreach ($interfaces as $iface) {
                    $name = $iface['name'] ?? '';
                    if (empty($name)) continue;

                    $rxBytes = (int) ($iface['rx-byte'] ?? 0);
                    $txBytes = (int) ($iface['tx-byte'] ?? 0);

                    InterfaceTraffic::updateOrCreate(
                        ['mikrotik_id' => $mikrotik->id, 'interface_name' => $name],
                        [
                            'rx_bytes' => $rxBytes,
                            'tx_bytes' => $txBytes,
                            'status' => ($iface['running'] ?? 'false') === 'true' ? 'up' : 'down',
                        ]
                    );

                    $trafficData[] = [
                        'interface' => $name,
                        'rx_bytes' => $rxBytes,
                        'tx_bytes' => $txBytes,
                    ];

                    $type = $iface['type'] ?? '';
                    if (in_array($type, ['ether', 'bridge', 'vlan']) && ($iface['running'] ?? 'false') === 'true') {
                        $total = $rxBytes + $txBytes;
                        if ($total > $maxBytes) {
                            $maxBytes = $total;
                            $topInterface = $name;
                        }
                    }
                }

                $rx = 0;
                $tx = 0;
                if ($topInterface) {
                    $monitor = $apiService->getInterfaceTraffic($mikrotik, $topInterface);
                    if (!empty($monitor[0])) {
                        $rx = (int) ($monitor[0]['rx-bits-per-second'] ?? 0);
                        $tx = (int) ($monitor[0]['tx-bits-per-second'] ?? 0);
                    }
                }

                TrafficUpdated::dispatch($mikrotik->id, $trafficData, $rx, $tx, $topInterface);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
