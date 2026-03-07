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
            'location' => 'NOC Trenggalek - Rack A1',
            'lat' => -8.0500,
            'lng' => 111.7065,
        ]);
        Mikrotik::create([
            'name' => 'MK-Dist-01',
            'host' => '192.168.2.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'admin123',
            'is_active' => true,
            'location' => 'POP Kelutan',
            'lat' => -8.0530,
            'lng' => 111.7100,
        ]);
        Mikrotik::create([
            'name' => 'MK-Dist-02',
            'host' => '192.168.3.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'admin123',
            'is_active' => true,
            'location' => 'POP Ngantru',
            'lat' => -8.0470,
            'lng' => 111.7030,
        ]);
    }
}
