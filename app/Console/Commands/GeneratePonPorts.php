<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Models\PonPort;
use Illuminate\Console\Command;

class GeneratePonPorts extends Command
{
    protected $signature = 'pon:generate {--slots=0 : Number of slots (0 for chassis-less)} {--ports=8 : Number of ports per slot}';
    protected $description = 'Generate PON ports for all OLTs that have no ports yet';

    public function handle()
    {
        $slots = (int) $this->option('slots');
        $portsPerSlot = (int) $this->option('ports');
        $created = 0;

        $olts = Olt::all();
        foreach ($olts as $olt) {
            $existing = PonPort::where('olt_id', $olt->id)->count();
            if ($existing > 0) {
                $this->info("OLT '{$olt->name}' already has {$existing} PON ports, skipping.");
                continue;
            }

            if ($slots === 0) {
                // Chassis-less OLT (like EPON small): just ports 1-N
                for ($p = 1; $p <= $portsPerSlot; $p++) {
                    PonPort::create([
                        'olt_id' => $olt->id,
                        'slot' => 0,
                        'port' => $p,
                        'description' => "PON {$p}",
                        'is_active' => true,
                    ]);
                    $created++;
                }
                $this->info("OLT '{$olt->name}': created {$portsPerSlot} PON ports (0/1 - 0/{$portsPerSlot})");
            } else {
                for ($s = 0; $s < $slots; $s++) {
                    for ($p = 1; $p <= $portsPerSlot; $p++) {
                        PonPort::create([
                            'olt_id' => $olt->id,
                            'slot' => $s,
                            'port' => $p,
                            'description' => "Slot {$s} PON {$p}",
                            'is_active' => true,
                        ]);
                        $created++;
                    }
                }
                $this->info("OLT '{$olt->name}': created " . ($slots * $portsPerSlot) . " PON ports ({$slots} slots x {$portsPerSlot} ports)");
            }
        }

        $this->info("Done! Total PON ports created: {$created}");
    }
}
