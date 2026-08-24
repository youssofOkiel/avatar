<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\ExternalLecturerSchedule;
use App\Models\Reservation;
use App\Models\TeacherSchedule;
use Illuminate\Support\Carbon;

class SessionOccurrenceService
{
    public function findOrCreateForTeacherSchedule(TeacherSchedule $schedule, Carbon $date): ClassSession
    {
        [$startsAt, $endsAt] = $this->buildDateTimes($date, (string) $schedule->starts_at, (string) $schedule->ends_at);

        $session = ClassSession::withTrashed()->firstOrCreate(
            [
                'teacher_schedule_id' => $schedule->id,
                'starts_at' => $startsAt,
            ],
            [
                'type' => 'subject',
                'teacher_id' => $schedule->teacher_id,
                'subject_id' => $schedule->subject_id,
                'education_level_id' => $schedule->education_level_id,
                'room_id' => $schedule->room_id,
                'income' => 0,
                'attendance_count' => null,
                'ends_at' => $endsAt,
            ],
        );

        if ($session->trashed()) {
            $session->restore();
        }

        $this->syncStudentsFromReservations($session, $schedule);

        return $session->fresh(['teacher', 'subject', 'room', 'students']);
    }

    public function findOrCreateForExternalSchedule(ExternalLecturerSchedule $schedule, Carbon $date): ClassSession
    {
        [$startsAt, $endsAt] = $this->buildDateTimes($date, (string) $schedule->starts_at, (string) $schedule->ends_at);

        $session = ClassSession::withTrashed()->firstOrCreate(
            [
                'external_lecturer_schedule_id' => $schedule->id,
                'starts_at' => $startsAt,
            ],
            [
                'type' => 'external',
                'teacher_id' => null,
                'subject_id' => null,
                'education_level_id' => null,
                'room_id' => $schedule->room_id,
                'title' => $schedule->topic,
                'income' => 0,
                'attendance_count' => null,
                'ends_at' => $endsAt,
            ],
        );

        if ($session->trashed()) {
            $session->restore();
        }

        return $session->fresh(['room']);
    }

    public function findOrCreateForExistingSession(ClassSession $session): ClassSession
    {
        return $session;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function buildDateTimes(Carbon $date, string $startTime, string $endTime): array
    {
        $startsAt = $this->combineDateAndTime($date, $startTime);
        $endsAt = $this->combineDateAndTime($date, $endTime);

        if ($endsAt->lte($startsAt)) {
            $endsAt = $startsAt->copy()->addMinutes(90);
        }

        return [$startsAt, $endsAt];
    }

    private function combineDateAndTime(Carbon $date, string $time): Carbon
    {
        $normalized = substr($time, 0, 8);
        if (strlen($normalized) === 5) {
            $normalized .= ':00';
        }

        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $normalized));

        return $date->copy()->setTime($hours, $minutes, $seconds);
    }

    private function syncStudentsFromReservations(ClassSession $session, TeacherSchedule $schedule): void
    {
        $studentIds = Reservation::query()
            ->where('teacher_id', $schedule->teacher_id)
            ->where('subject_id', $schedule->subject_id)
            ->where('education_level_id', $schedule->education_level_id)
            ->whereHas('teacherSchedules', fn ($query) => $query->where('teacher_schedules.id', $schedule->id))
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $existing = $session->students()
            ->get()
            ->mapWithKeys(fn ($student): array => [
                $student->id => ['attended' => (bool) $student->pivot->attended],
            ])
            ->all();

        foreach ($studentIds as $studentId) {
            if (! isset($existing[$studentId])) {
                $existing[$studentId] = ['attended' => false];
            }
        }

        $session->students()->sync($existing);
    }
}
