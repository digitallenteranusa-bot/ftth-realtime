<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->foreignId('pon_port_id')->nullable()->after('olt_id')->constrained('pon_ports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['pon_port_id']);
            $table->dropColumn('pon_port_id');
        });
    }
};
