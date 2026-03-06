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

                foreach ($interfaces as $iface) {
                    $name = $iface['name'] ?? '';
                    if (empty($name)) continue;

                    InterfaceTraffic::updateOrCreate(
                        ['mikrotik_id' => $mikrotik->id, 'interface_name' => $name],
                        [
                            'rx_bytes' => $iface['rx-byte'] ?? 0,
                            'tx_bytes' => $iface['tx-byte'] ?? 0,
                            'status' => ($iface['running'] ?? 'false') === 'true' ? 'up' : 'down',
                        ]
                    );

                    $trafficData[] = [
                        'interface' => $name,
                        'rx_bytes' => $iface['rx-byte'] ?? 0,
                        'tx_bytes' => $iface['tx-byte'] ?? 0,
                    ];
                }

                TrafficUpdated::dispatch($mikrotik->id, $trafficData);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
