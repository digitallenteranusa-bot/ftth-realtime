<?php
namespace App\Jobs;

use App\Events\OntStatusChanged;
use App\Models\Alarm;
use App\Events\AlarmTriggered;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
use App\Services\Olt\OltServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollOltJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $olts = Olt::where('is_active', true)->with('ponPorts')->get();

        foreach ($olts as $olt) {
            try {
                $driver = OltServiceFactory::make($olt);
                if (!$driver->connect()) continue;

                foreach ($olt->ponPorts as $ponPort) {
                    $ontList = $driver->getOntList($ponPort->slot, $ponPort->port);

                    foreach ($ontList as $ontData) {
                        $status = $this->normalizeStatus($ontData['oper_state'] ?? $ontData['run_state'] ?? $ontData['status'] ?? 'unknown');

                        $ont = Ont::where('olt_id', $olt->id)
                            ->where('pon_port_id', $ponPort->id)
                            ->where('ont_id_number', $ontData['ont_id'])
                            ->first();

                        if ($ont) {
                            $oldStatus = $ont->status;
                            $ont->update([
                                'status' => $status,
                                'serial_number' => $ontData['serial'] ?? $ont->serial_number,
                                'last_online_at' => $status === 'online' ? now() : $ont->last_online_at,
                            ]);

                            // Get optical power
                            $power = $driver->getOpticalPower($ponPort->slot, $ponPort->port, $ontData['ont_id']);
                            if (!empty($power)) {
                                $ont->update([
                                    'rx_power' => $power['rx_power'] ?? $ont->rx_power,
                                    'tx_power' => $power['tx_power'] ?? $ont->tx_power,
                                ]);
                            }

                            if ($oldStatus !== $status) {
                                OntStatusChanged::dispatch($ont, $oldStatus);

                                if ($status === 'los' || $status === 'offline') {
                                    $alarm = Alarm::create([
                                        'device_type' => Ont::class,
                                        'device_id' => $ont->id,
                                        'severity' => $status === 'los' ? 'critical' : 'major',
                                        'title' => "ONT {$status}: {$ont->serial_number}",
                                        'description' => "ONT changed from {$oldStatus} to {$status}",
                                    ]);
                                    AlarmTriggered::dispatch($alarm);
                                }
                            }
                        }
                    }
                }

                $driver->disconnect();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    protected function normalizeStatus(string $status): string
    {
        $status = strtolower($status);
        return match (true) {
            str_contains($status, 'online') || str_contains($status, 'working') => 'online',
            str_contains($status, 'los') => 'los',
            str_contains($status, 'dying') => 'dyinggasp',
            str_contains($status, 'offline') || str_contains($status, 'down') => 'offline',
            default => 'unknown',
        };
    }
}
