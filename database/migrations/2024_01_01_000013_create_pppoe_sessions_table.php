<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pppoe_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_id')->constrained('mikrotiks')->cascadeOnDelete();
            $table->string('username');
            $table->string('caller_id')->nullable();
            $table->string('service')->nullable();
            $table->string('uptime')->nullable();
            $table->string('ip_address')->nullable();
            $table->bigInteger('rx_bytes')->default(0);
            $table->bigInteger('tx_bytes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mikrotik_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pppoe_sessions');
    }
};
