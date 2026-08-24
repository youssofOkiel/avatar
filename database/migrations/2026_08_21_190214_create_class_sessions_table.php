<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('education_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_schedule_id')
                ->nullable()
                ->constrained('teacher_schedules')
                ->nullOnDelete();
            $table->foreignId('external_lecturer_schedule_id')
                ->nullable()
                ->constrained('external_lecturer_schedules')
                ->nullOnDelete();
            $table->string('type')->default('subject');
            $table->string('title')->nullable();
            $table->decimal('income', 10, 2)->default(0);
            $table->unsignedInteger('attendance_count')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamp('outcome_recorded_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['teacher_schedule_id', 'starts_at'], 'class_sessions_teacher_schedule_starts_unique');
            $table->unique(['external_lecturer_schedule_id', 'starts_at'], 'class_sessions_external_schedule_starts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
