<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->foreignId('olt_id')->nullable()->after('id')->constrained('olts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropColumn('olt_id');
        });
    }
};
