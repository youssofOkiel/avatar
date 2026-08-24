<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\ExternalLecturerSchedule;
use App\Models\TeacherSchedule;

trait ChecksRoomScheduleConflicts
{
    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    protected function hasRoomConflict(
        array $schedules,
        int $index,
        ?int $excludeTeacherId = null,
        ?int $excludeExternalLecturerId = null,
    ): bool {
        $schedule = $schedules[$index];
        $roomId = $schedule['room_id'] ?? null;
        $day = $schedule['day_of_week'] ?? null;
        $start = $schedule['starts_at'] ?? null;
        $end = $schedule['ends_at'] ?? null;

        if ($roomId === null || $roomId === '' || $day === null || $start === null || $end === null) {
            return false;
        }

        $roomId = (int) $roomId;
        $day = (int) $day;
        $start = (string) $start;
        $end = (string) $end;

        return $this->roomOverlapsAnotherRow($schedules, $index, $roomId, $day, $start, $end)
            || $this->roomOverlapsExistingTeacherSchedule($roomId, $day, $start, $end, $excludeTeacherId)
            || $this->roomOverlapsExistingExternalSchedule($roomId, $day, $start, $end, $excludeExternalLecturerId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    private function roomOverlapsAnotherRow(
        array $schedules,
        int $currentIndex,
        int $roomId,
        int $day,
        string $start,
        string $end,
    ): bool {
        foreach ($schedules as $index => $other) {
            if ($index === $currentIndex) {
                continue;
            }

            $otherRoomId = $other['room_id'] ?? null;

            if ($otherRoomId === null || $otherRoomId === ''
                || (int) $otherRoomId !== $roomId
                || (int) ($other['day_of_week'] ?? -1) !== $day) {
                continue;
            }

            $otherStart = $other['starts_at'] ?? null;
            $otherEnd = $other['ends_at'] ?? null;

            if ($otherStart === null || $otherEnd === null) {
                continue;
            }

            if ($this->timesOverlap($start, $end, (string) $otherStart, (string) $otherEnd)) {
                return true;
            }
        }

        return false;
    }

    private function roomOverlapsExistingTeacherSchedule(
        int $roomId,
        int $day,
        string $start,
        string $end,
        ?int $excludeTeacherId,
    ): bool {
        return TeacherSchedule::query()
            ->where('room_id', $roomId)
            ->where('day_of_week', $day)
            ->when($excludeTeacherId, fn ($query, $teacherId) => $query->where('teacher_id', '!=', $teacherId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    private function roomOverlapsExistingExternalSchedule(
        int $roomId,
        int $day,
        string $start,
        string $end,
        ?int $excludeExternalLecturerId,
    ): bool {
        return ExternalLecturerSchedule::query()
            ->where('room_id', $roomId)
            ->where('day_of_week', $day)
            ->when(
                $excludeExternalLecturerId,
                fn ($query, $lecturerId) => $query->where('external_lecturer_id', '!=', $lecturerId)
            )
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    private function timesOverlap(string $start, string $end, string $otherStart, string $otherEnd): bool
    {
        return $otherStart < $end && $otherEnd > $start;
    }
}
