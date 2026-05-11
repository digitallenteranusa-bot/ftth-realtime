<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ftth.local'],
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'role' => 'admin']
        );
        User::firstOrCreate(
            ['email' => 'operator@ftth.local'],
            ['name' => 'Operator 1', 'password' => Hash::make('password'), 'role' => 'operator']
        );
    }
}
