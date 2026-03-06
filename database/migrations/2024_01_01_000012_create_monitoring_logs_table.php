<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_type');
            $table->unsignedBigInteger('device_id');
            $table->json('data');
            $table->timestamps();

            $table->index(['device_type', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_logs');
    }
};
