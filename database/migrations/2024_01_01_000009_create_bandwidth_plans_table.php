<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bandwidth_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('upload_speed');
            $table->integer('download_speed');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandwidth_plans');
    }
};
