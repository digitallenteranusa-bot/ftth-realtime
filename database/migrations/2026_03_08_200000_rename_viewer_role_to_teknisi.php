<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Tambah 'teknisi' ke ENUM (viewer masih ada)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'viewer', 'teknisi') NOT NULL DEFAULT 'operator'");

        // Step 2: Update data viewer -> teknisi
        DB::table('users')->where('role', 'viewer')->update(['role' => 'teknisi']);

        // Step 3: Hapus 'viewer' dari ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'teknisi') NOT NULL DEFAULT 'teknisi'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'viewer', 'teknisi') NOT NULL DEFAULT 'operator'");
        DB::table('users')->where('role', 'teknisi')->update(['role' => 'viewer']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'viewer') NOT NULL DEFAULT 'operator'");
    }
};
