<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('olt_id')->nullable()->constrained('olts')->cascadeOnDelete();
            $table->foreignId('pon_port_id')->nullable()->constrained('pon_ports')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->integer('ont_id_number')->nullable();
            $table->enum('status', ['online', 'offline', 'los', 'dyinggasp', 'unknown'])->default('unknown');
            $table->decimal('rx_power', 5, 2)->nullable();
            $table->decimal('tx_power', 5, 2)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('last_online_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onts');
    }
};
