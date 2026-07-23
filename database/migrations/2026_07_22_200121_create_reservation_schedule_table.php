<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_schedule_id')->constrained()->cascadeOnDelete();

            $table->unique(['reservation_id', 'teacher_schedule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_schedule');
    }
};
