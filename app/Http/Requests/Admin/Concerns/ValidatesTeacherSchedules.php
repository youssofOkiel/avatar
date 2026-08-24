<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\TeacherSchedule;
use Illuminate\Validation\Validator;

trait ValidatesTeacherSchedules
{
    use ChecksRoomScheduleConflicts;

    /**
     * The teacher id being edited, so its own schedules are excluded from the
     * cross-teacher conflict check. Null when creating.
     */
    abstract protected function currentTeacherId(): ?int;

    /**
     * Reject when the same room is already occupied at an overlapping time by any
     * teacher or external lecturer schedule.
     *
     * Same-education-level time overlap validation is temporarily disabled;
     * overlaps are still flagged on the schedule UI via flagLevelConflicts().
     * Re-enable by restoring the overlapsAnotherRow / overlapsExistingSchedule
     * checks below.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array<string, mixed>> $schedules */
            $schedules = $this->input('schedules', []);

            foreach ($schedules as $index => $schedule) {
                $start = $schedule['starts_at'] ?? null;
                $end = $schedule['ends_at'] ?? null;
                $day = $schedule['day_of_week'] ?? null;

                if ($start === null || $end === null || $day === null) {
                    continue;
                }

                if ($end <= $start) {
                    $validator->errors()->add("schedules.{$index}.ends_at", 'وقت النهاية يجب أن يكون بعد وقت البداية.');

                    continue;
                }

                // Same-education-level overlap rejection — disabled for now.
                // if ($this->overlapsAnotherRow(...) || $this->overlapsExistingSchedule(...)) {
                //     $validator->errors()->add("schedules.{$index}.starts_at", '...');
                // }

                if ($this->hasRoomConflict(
                    $schedules,
                    $index,
                    excludeTeacherId: $this->currentTeacherId(),
                    excludeExternalLecturerId: null,
                )) {
                    $validator->errors()->add(
                        "schedules.{$index}.room_id",
                        'القاعة محجوزة في نفس الفترة الزمنية (معلم أو محاضر خارجي).'
                    );
                }
            }
        });
    }

    /**
     * Temporarily unused — kept for re-enabling same-level overlap validation.
     *
     * @param  array<int, array<string, mixed>>  $schedules
     */
    private function overlapsAnotherRow(array $schedules, int $currentIndex, int $levelId, int $day, string $start, string $end): bool
    {
        foreach ($schedules as $index => $other) {
            if ($index === $currentIndex) {
                continue;
            }

            if ((int) ($other['education_level_id'] ?? 0) !== $levelId
                || (int) ($other['day_of_week'] ?? -1) !== $day) {
                continue;
            }

            $otherStart = $other['starts_at'] ?? null;
            $otherEnd = $other['ends_at'] ?? null;

            if ($otherStart === null || $otherEnd === null) {
                continue;
            }

            if ($otherStart < $end && $otherEnd > $start) {
                return true;
            }
        }

        return false;
    }

    /**
     * Temporarily unused — kept for re-enabling same-level overlap validation.
     */
    private function overlapsExistingSchedule(int $levelId, int $day, string $start, string $end): bool
    {
        return TeacherSchedule::query()
            ->where('education_level_id', $levelId)
            ->where('day_of_week', $day)
            ->when($this->currentTeacherId(), fn ($query, $teacherId) => $query->where('teacher_id', '!=', $teacherId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }
}
