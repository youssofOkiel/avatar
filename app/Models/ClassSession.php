<?php

namespace App\Models;

use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'teacher_id',
    'subject_id',
    'education_level_id',
    'room_id',
    'teacher_schedule_id',
    'external_lecturer_schedule_id',
    'type',
    'title',
    'income',
    'attendance_count',
    'starts_at',
    'ends_at',
    'outcome_recorded_at',
    'canceled_at',
])]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'outcome_recorded_at' => 'datetime',
            'canceled_at' => 'datetime',
            'income' => 'decimal:2',
            'attendance_count' => 'integer',
        ];
    }

    /**
     * Recorded headcount for this session, when provided.
     */
    public function attendanceNumber(): int
    {
        return (int) ($this->attendance_count ?? 0);
    }

    public function hasRecordedOutcome(): bool
    {
        return $this->outcome_recorded_at !== null && $this->canceled_at === null;
    }

    public function isCanceled(): bool
    {
        return $this->canceled_at !== null;
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<EducationLevel, $this>
     */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<TeacherSchedule, $this>
     */
    public function teacherSchedule(): BelongsTo
    {
        return $this->belongsTo(TeacherSchedule::class);
    }

    /**
     * @return BelongsTo<ExternalLecturerSchedule, $this>
     */
    public function externalLecturerSchedule(): BelongsTo
    {
        return $this->belongsTo(ExternalLecturerSchedule::class);
    }

    /**
     * The students attending this (exceptional) session.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_session_student')
            ->withPivot('attended');
    }

    /**
     * @param  Builder<ClassSession>  $query
     * @return Builder<ClassSession>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    /**
     * @param  Builder<ClassSession>  $query
     * @return Builder<ClassSession>
     */
    public function scopeWithRecordedOutcome(Builder $query): Builder
    {
        return $query
            ->whereNotNull('outcome_recorded_at')
            ->whereNull('canceled_at');
    }

    /**
     * @param  Builder<ClassSession>  $query
     * @return Builder<ClassSession>
     */
    public function scopePendingOutcome(Builder $query): Builder
    {
        return $query
            ->whereNull('outcome_recorded_at')
            ->whereNull('canceled_at')
            ->where('starts_at', '<', now());
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }
}
