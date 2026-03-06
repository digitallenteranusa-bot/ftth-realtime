<?php
namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Budi Santoso', 'address' => 'Jl. Raya Darmo 45', 'phone' => '081234567001', 'nik' => '3578012345670001'],
            ['name' => 'Siti Rahayu', 'address' => 'Jl. Diponegoro 12', 'phone' => '081234567002', 'nik' => '3578012345670002'],
            ['name' => 'Ahmad Hidayat', 'address' => 'Jl. Basuki Rahmat 78', 'phone' => '081234567003', 'nik' => '3578012345670003'],
            ['name' => 'Dewi Lestari', 'address' => 'Jl. Pemuda 33', 'phone' => '081234567004', 'nik' => '3578012345670004'],
            ['name' => 'Eko Prasetyo', 'address' => 'Jl. Gubeng 56', 'phone' => '081234567005', 'nik' => '3578012345670005'],
            ['name' => 'Fitri Handayani', 'address' => 'Jl. Mayjen Sungkono 90', 'phone' => '081234567006', 'nik' => '3578012345670006'],
            ['name' => 'Gunawan Wibowo', 'address' => 'Jl. HR Muhammad 23', 'phone' => '081234567007', 'nik' => '3578012345670007'],
            ['name' => 'Hesti Purnama', 'address' => 'Jl. Kertajaya 67', 'phone' => '081234567008', 'nik' => '3578012345670008'],
            ['name' => 'Irwan Setiawan', 'address' => 'Jl. Manyar 14', 'phone' => '081234567009', 'nik' => '3578012345670009'],
            ['name' => 'Joko Susanto', 'address' => 'Jl. Rungkut 88', 'phone' => '081234567010', 'nik' => '3578012345670010'],
            ['name' => 'Kartini Sari', 'address' => 'Jl. Dharmahusada 5', 'phone' => '081234567011', 'nik' => '3578012345670011'],
            ['name' => 'Lukman Hakim', 'address' => 'Jl. Mulyosari 29', 'phone' => '081234567012', 'nik' => '3578012345670012'],
            ['name' => 'Maya Anggraini', 'address' => 'Jl. Kenjeran 42', 'phone' => '081234567013', 'nik' => '3578012345670013'],
            ['name' => 'Nanda Pratama', 'address' => 'Jl. Kapasan 61', 'phone' => '081234567014', 'nik' => '3578012345670014'],
            ['name' => 'Oki Firmansyah', 'address' => 'Jl. Tandes 17', 'phone' => '081234567015', 'nik' => '3578012345670015'],
            ['name' => 'Putri Maharani', 'address' => 'Jl. Wiyung 38', 'phone' => '081234567016', 'nik' => '3578012345670016'],
            ['name' => 'Rahmat Kurniawan', 'address' => 'Jl. Lakarsantri 52', 'phone' => '081234567017', 'nik' => '3578012345670017'],
            ['name' => 'Sari Widiastuti', 'address' => 'Jl. Sukolilo 75', 'phone' => '081234567018', 'nik' => '3578012345670018'],
            ['name' => 'Teguh Wijaya', 'address' => 'Jl. Wonokromo 19', 'phone' => '081234567019', 'nik' => '3578012345670019'],
            ['name' => 'Umar Abdullah', 'address' => 'Jl. Semampir 84', 'phone' => '081234567020', 'nik' => '3578012345670020'],
        ];

        foreach ($customers as $i => $c) {
            $c['lat'] = -7.2575 + ($i * 0.002);
            $c['lng'] = 112.7400 + (($i % 5) * 0.005);
            $c['status'] = 'active';
            Customer::create($c);
        }
    }
}
