<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->string('device_type');
            $table->unsignedBigInteger('device_id');
            $table->enum('severity', ['critical', 'major', 'minor', 'warning', 'info'])->default('info');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['device_type', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
