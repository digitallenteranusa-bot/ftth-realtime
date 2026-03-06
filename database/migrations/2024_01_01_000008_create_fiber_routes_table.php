<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiber_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('destination_type');
            $table->unsignedBigInteger('destination_id');
            $table->json('coordinates');
            $table->string('color')->default('#3388ff');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['destination_type', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_routes');
    }
};
