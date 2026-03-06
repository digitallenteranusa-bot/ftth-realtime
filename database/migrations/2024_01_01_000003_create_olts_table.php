<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('vendor', ['zte', 'huawei', 'fiberhome']);
            $table->string('host');
            $table->integer('telnet_port')->default(23);
            $table->integer('ssh_port')->default(22);
            $table->string('username');
            $table->text('password');
            $table->string('snmp_community')->default('public');
            $table->integer('snmp_port')->default(161);
            $table->boolean('is_active')->default(true);
            $table->string('location')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
