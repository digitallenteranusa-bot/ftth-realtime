<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interface_traffics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_id')->constrained('mikrotiks')->cascadeOnDelete();
            $table->string('interface_name');
            $table->bigInteger('rx_bytes')->default(0);
            $table->bigInteger('tx_bytes')->default(0);
            $table->bigInteger('rx_rate')->default(0);
            $table->bigInteger('tx_rate')->default(0);
            $table->string('status')->default('up');
            $table->timestamps();

            $table->index(['mikrotik_id', 'interface_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interface_traffics');
    }
};
