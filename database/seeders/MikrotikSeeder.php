<?php
namespace Database\Seeders;

use App\Models\Mikrotik;
use Illuminate\Database\Seeder;

class MikrotikSeeder extends Seeder
{
    public function run(): void
    {
        Mikrotik::create([
            'name' => 'MK-Core-01',
            'host' => '192.168.1.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'admin123',
            'is_active' => true,
            'location' => 'NOC Room - Rack A1',
            'lat' => -7.2575,
            'lng' => 112.7520,
        ]);
        Mikrotik::create([
            'name' => 'MK-Dist-01',
            'host' => '192.168.2.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'admin123',
            'is_active' => true,
            'location' => 'POP Darmo',
            'lat' => -7.2650,
            'lng' => 112.7380,
        ]);
        Mikrotik::create([
            'name' => 'MK-Dist-02',
            'host' => '192.168.3.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'admin123',
            'is_active' => true,
            'location' => 'POP Gubeng',
            'lat' => -7.2700,
            'lng' => 112.7550,
        ]);
    }
}
