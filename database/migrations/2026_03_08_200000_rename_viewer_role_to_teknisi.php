<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah ENUM: ganti viewer jadi teknisi
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'teknisi') NOT NULL DEFAULT 'teknisi'");
        DB::table('users')->where('role', 'viewer')->update(['role' => 'teknisi']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'teknisi')->update(['role' => 'viewer']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'viewer') NOT NULL DEFAULT 'operator'");
    }
};
