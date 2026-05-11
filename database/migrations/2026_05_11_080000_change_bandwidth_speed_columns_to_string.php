<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bandwidth_plans', function (Blueprint $table) {
            $table->string('upload_speed', 50)->change();
            $table->string('download_speed', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bandwidth_plans', function (Blueprint $table) {
            $table->integer('upload_speed')->change();
            $table->integer('download_speed')->change();
        });
    }
};
