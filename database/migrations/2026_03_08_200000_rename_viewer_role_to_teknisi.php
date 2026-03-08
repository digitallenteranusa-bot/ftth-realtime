<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'viewer')->update(['role' => 'teknisi']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'teknisi')->update(['role' => 'viewer']);
    }
};
