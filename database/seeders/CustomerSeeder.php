<?php
namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Budi Santoso', 'address' => 'Jl. Raya Kelutan 45, Trenggalek', 'phone' => '081234567001', 'nik' => '3503012345670001'],
            ['name' => 'Siti Rahayu', 'address' => 'Jl. Suontoro 12, Trenggalek', 'phone' => '081234567002', 'nik' => '3503012345670002'],
            ['name' => 'Ahmad Hidayat', 'address' => 'Jl. Brigjen Sutran 78, Trenggalek', 'phone' => '081234567003', 'nik' => '3503012345670003'],
            ['name' => 'Dewi Lestari', 'address' => 'Jl. MT Haryono 33, Trenggalek', 'phone' => '081234567004', 'nik' => '3503012345670004'],
            ['name' => 'Eko Prasetyo', 'address' => 'Jl. Hayam Wuruk 56, Trenggalek', 'phone' => '081234567005', 'nik' => '3503012345670005'],
            ['name' => 'Fitri Handayani', 'address' => 'Jl. Raya Ngantru 90, Trenggalek', 'phone' => '081234567006', 'nik' => '3503012345670006'],
            ['name' => 'Gunawan Wibowo', 'address' => 'Jl. Soekarno Hatta 23, Trenggalek', 'phone' => '081234567007', 'nik' => '3503012345670007'],
            ['name' => 'Hesti Purnama', 'address' => 'Jl. Raya Pogalan 67, Trenggalek', 'phone' => '081234567008', 'nik' => '3503012345670008'],
            ['name' => 'Irwan Setiawan', 'address' => 'Jl. Raya Karangan 14, Trenggalek', 'phone' => '081234567009', 'nik' => '3503012345670009'],
            ['name' => 'Joko Susanto', 'address' => 'Jl. Raya Gandusari 88, Trenggalek', 'phone' => '081234567010', 'nik' => '3503012345670010'],
            ['name' => 'Kartini Sari', 'address' => 'Jl. Raya Tugu 5, Trenggalek', 'phone' => '081234567011', 'nik' => '3503012345670011'],
            ['name' => 'Lukman Hakim', 'address' => 'Jl. Raya Durenan 29, Trenggalek', 'phone' => '081234567012', 'nik' => '3503012345670012'],
            ['name' => 'Maya Anggraini', 'address' => 'Jl. Raya Kampak 42, Trenggalek', 'phone' => '081234567013', 'nik' => '3503012345670013'],
            ['name' => 'Nanda Pratama', 'address' => 'Jl. Raya Watulimo 61, Trenggalek', 'phone' => '081234567014', 'nik' => '3503012345670014'],
            ['name' => 'Oki Firmansyah', 'address' => 'Jl. Raya Munjungan 17, Trenggalek', 'phone' => '081234567015', 'nik' => '3503012345670015'],
            ['name' => 'Putri Maharani', 'address' => 'Jl. Raya Panggul 38, Trenggalek', 'phone' => '081234567016', 'nik' => '3503012345670016'],
            ['name' => 'Rahmat Kurniawan', 'address' => 'Jl. Raya Bendungan 52, Trenggalek', 'phone' => '081234567017', 'nik' => '3503012345670017'],
            ['name' => 'Sari Widiastuti', 'address' => 'Jl. Raya Pule 75, Trenggalek', 'phone' => '081234567018', 'nik' => '3503012345670018'],
            ['name' => 'Teguh Wijaya', 'address' => 'Jl. Raya Suruh 19, Trenggalek', 'phone' => '081234567019', 'nik' => '3503012345670019'],
            ['name' => 'Umar Abdullah', 'address' => 'Jl. Raya Dongko 84, Trenggalek', 'phone' => '081234567020', 'nik' => '3503012345670020'],
        ];

        foreach ($customers as $i => $c) {
            $c['lat'] = -8.0480 + ($i * 0.0015);
            $c['lng'] = 111.7020 + (($i % 5) * 0.003);
            $c['status'] = 'active';
            Customer::create($c);
        }
    }
}
