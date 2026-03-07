<?php
namespace Database\Seeders;

use App\Models\Olt;
use App\Models\PonPort;
use Illuminate\Database\Seeder;

class OltSeeder extends Seeder
{
    public function run(): void
    {
        $olt1 = Olt::create([
            'name' => 'OLT-ZTE-01',
            'vendor' => 'zte',
            'host' => '10.10.10.1',
            'telnet_port' => 23,
            'ssh_port' => 22,
            'username' => 'admin',
            'password' => 'admin123',
            'is_active' => true,
            'location' => 'NOC Trenggalek - Rack B1',
            'lat' => -8.0505,
            'lng' => 111.7070,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            PonPort::create(['olt_id' => $olt1->id, 'slot' => 1, 'port' => $i, 'description' => "PON 1/{$i}", 'is_active' => true]);
        }

        $olt2 = Olt::create([
            'name' => 'OLT-HW-01',
            'vendor' => 'huawei',
            'host' => '10.10.10.2',
            'telnet_port' => 23,
            'ssh_port' => 22,
            'username' => 'admin',
            'password' => 'admin123',
            'is_active' => true,
            'location' => 'NOC Trenggalek - Rack B2',
            'lat' => -8.0508,
            'lng' => 111.7073,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            PonPort::create(['olt_id' => $olt2->id, 'slot' => 0, 'port' => $i, 'description' => "PON 0/{$i}", 'is_active' => true]);
        }
    }
}
