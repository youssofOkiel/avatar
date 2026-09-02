<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\ExternalLecturerSchedule;
use App\Models\Room;
use App\Models\TeacherSchedule;
use App\Support\GroupedEducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $reference = $this->parseDate($request->query('date'));
        $weekStart = $reference->copy()->startOfWeek(Carbon::SATURDAY);
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $rooms = Room::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $teacherSchedules = TeacherSchedule::query()
            ->with(['teacher:id,name', 'subject:id,name', 'educationLevel:id,name,education_level_group_id', 'educationLevel.group:id,name', 'room:id,name'])
            ->get();

        $externalSchedules = ExternalLecturerSchedule::query()
            ->with(['externalLecturer:id,name', 'room:id,name'])
            ->get();

        $sessions = ClassSession::query()
            ->with(['teacher:id,name', 'subject:id,name', 'room:id,name'])
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->get();

        $teacherOccurrences = $sessions
            ->whereNotNull('teacher_schedule_id')
            ->keyBy(fn (ClassSession $session): string => $session->teacher_schedule_id.'|'.$session->starts_at->toDateString());

        $externalOccurrences = $sessions
            ->whereNotNull('external_lecturer_schedule_id')
            ->keyBy(fn (ClassSession $session): string => $session->external_lecturer_schedule_id.'|'.$session->starts_at->toDateString());

        $days = collect(range(0, 6))
            ->map(function (int $offset) use ($weekStart, $rooms, $teacherSchedules, $externalSchedules, $sessions, $teacherOccurrences, $externalOccurrences): array {
                $date = $weekStart->copy()->addDays($offset);
                $dow = (int) $date->dayOfWeek;
                $dateString = $date->toDateString();

                /** @var Collection<int, array<string, mixed>> $items */
                $items = collect();

                foreach ($teacherSchedules->where('day_of_week', $dow) as $schedule) {
                    $occurrence = $teacherOccurrences->get($schedule->id.'|'.$dateString);
                    $plannedStarts = substr((string) $schedule->starts_at, 0, 5);
                    $plannedEnds = substr((string) $schedule->ends_at, 0, 5);

                    $items->push($this->item(
                        kind: 'teacher',
                        title: $schedule->subject?->name,
                        who: $schedule->teacher?->name,
                        level: $schedule->educationLevel?->name,
                        levelGroup: $schedule->educationLevel?->group?->name,
                        educationLevelId: $schedule->education_level_id,
                        educationLevelGroupId: $schedule->educationLevel?->education_level_group_id,
                        roomId: $schedule->room_id,
                        startsAt: $occurrence?->starts_at->format('H:i') ?? $plannedStarts,
                        endsAt: $occurrence?->ends_at->format('H:i') ?? $plannedEnds,
                        plannedStartsAt: $plannedStarts,
                        plannedEndsAt: $plannedEnds,
                        occurrenceKind: 'teacher',
                        occurrenceId: $schedule->id,
                        sessionId: $occurrence?->id,
                        actualIncome: $occurrence !== null ? (float) $occurrence->income : null,
                        sessionStatus: $this->sessionStatus($date, (string) $schedule->starts_at, $occurrence),
                    ));
                }

                foreach ($externalSchedules->where('day_of_week', $dow) as $schedule) {
                    $occurrence = $externalOccurrences->get($schedule->id.'|'.$dateString);
                    $plannedStarts = substr((string) $schedule->starts_at, 0, 5);
                    $plannedEnds = substr((string) $schedule->ends_at, 0, 5);

                    $items->push($this->item(
                        kind: 'external',
                        title: $schedule->topic,
                        who: $schedule->externalLecturer?->name,
                        level: null,
                        levelGroup: null,
                        educationLevelId: null,
                        educationLevelGroupId: null,
                        roomId: $schedule->room_id,
                        startsAt: $occurrence?->starts_at->format('H:i') ?? $plannedStarts,
                        endsAt: $occurrence?->ends_at->format('H:i') ?? $plannedEnds,
                        plannedStartsAt: $plannedStarts,
                        plannedEndsAt: $plannedEnds,
                        occurrenceKind: 'external',
                        occurrenceId: $schedule->id,
                        sessionId: $occurrence?->id,
                        actualIncome: $occurrence !== null ? (float) $occurrence->income : null,
                        sessionStatus: $this->sessionStatus($date, (string) $schedule->starts_at, $occurrence),
                    ));
                }

                foreach ($sessions->filter(function (ClassSession $session) use ($date): bool {
                    if ($session->teacher_schedule_id !== null || $session->external_lecturer_schedule_id !== null) {
                        return false;
                    }

                    return $session->starts_at->isSameDay($date);
                }) as $session) {
                    $items->push($this->item(
                        kind: 'session',
                        title: in_array($session->type, ['rental', 'external'], true) ? $session->title : $session->subject?->name,
                        who: $session->type === 'rental' ? null : $session->teacher?->name,
                        level: null,
                        levelGroup: null,
                        educationLevelId: null,
                        educationLevelGroupId: null,
                        roomId: $session->room_id,
                        startsAt: $session->starts_at->format('H:i'),
                        endsAt: $session->ends_at->format('H:i'),
                        plannedStartsAt: $session->starts_at->format('H:i'),
                        plannedEndsAt: $session->ends_at->format('H:i'),
                        occurrenceKind: 'session',
                        occurrenceId: $session->id,
                        sessionId: $session->id,
                        actualIncome: (float) $session->income,
                        sessionStatus: $this->sessionStatus($date, $session->starts_at->format('H:i:s'), $session),
                    ));
                }

                $items = $this->flagLevelConflicts($items);
                $items = $this->flagRoomConflicts($items);

                $cells = $rooms
                    ->map(fn (Room $room): array => [
                        'room_id' => $room->id,
                        'items' => $items
                            ->where('room_id', $room->id)
                            ->sortBy('starts_at')
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all();

                $unassigned = $items
                    ->whereNull('room_id')
                    ->sortBy('starts_at')
                    ->values()
                    ->all();

                return [
                    'date' => $dateString,
                    'day_of_week' => $dow,
                    'is_today' => $date->isToday(),
                    'cells' => $cells,
                    'unassigned' => $unassigned,
                ];
            })
            ->all();

        return Inertia::render('admin/schedule/index', [
            'week' => [
                'start' => $weekStart->toDateString(),
                'end' => $weekStart->copy()->addDays(6)->toDateString(),
                'prev' => $weekStart->copy()->subWeek()->toDateString(),
                'next' => $weekStart->copy()->addWeek()->toDateString(),
                'today' => now()->toDateString(),
            ],
            'rooms' => $rooms,
            'levelGroups' => GroupedEducationLevels::grouped(),
            'days' => $days,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function flagLevelConflicts(Collection $items): Collection
    {
        $indexed = $items->values();

        foreach ($indexed as $i => $item) {
            if (($item['kind'] ?? null) !== 'teacher' || ($item['education_level_id'] ?? null) === null) {
                continue;
            }

            foreach ($indexed as $j => $other) {
                if ($i === $j
                    || ($other['kind'] ?? null) !== 'teacher'
                    || ($other['education_level_id'] ?? null) !== $item['education_level_id']) {
                    continue;
                }

                if ($item['starts_at'] < $other['ends_at'] && $item['ends_at'] > $other['starts_at']) {
                    $indexed[$i] = [...$item, 'has_level_conflict' => true];
                    break;
                }
            }
        }

        return $indexed;
    }

    /**
     * Mark items that share a room with an overlapping time range.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function flagRoomConflicts(Collection $items): Collection
    {
        $indexed = $items->values();

        foreach ($indexed as $i => $item) {
            if (($item['room_id'] ?? null) === null) {
                continue;
            }

            foreach ($indexed as $j => $other) {
                if ($i === $j || ($other['room_id'] ?? null) !== $item['room_id']) {
                    continue;
                }

                if ($item['starts_at'] < $other['ends_at'] && $item['ends_at'] > $other['starts_at']) {
                    $indexed[$i] = [...$item, 'has_room_conflict' => true];
                    break;
                }
            }
        }

        return $indexed;
    }

    private function sessionStatus(Carbon $date, string $startTime, ?ClassSession $occurrence): string
    {
        if ($occurrence?->isCanceled()) {
            return 'canceled';
        }

        $time = substr($startTime, 0, 8);
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time));
        $startsAt = $date->copy()->setTime($hours, $minutes, $seconds);

        if ($startsAt->isFuture()) {
            return 'upcoming';
        }

        if ($occurrence?->hasRecordedOutcome()) {
            return 'recorded';
        }

        return 'pending';
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $kind,
        ?string $title,
        ?string $who,
        ?string $level,
        ?string $levelGroup,
        ?int $educationLevelId,
        ?int $educationLevelGroupId,
        ?int $roomId,
        string $startsAt,
        string $endsAt,
        string $plannedStartsAt,
        string $plannedEndsAt,
        string $occurrenceKind,
        int $occurrenceId,
        ?int $sessionId,
        ?float $actualIncome,
        string $sessionStatus,
    ): array {
        return [
            'kind' => $kind,
            'title' => $title,
            'who' => $who,
            'level' => $level,
            'level_group' => $levelGroup,
            'education_level_id' => $educationLevelId,
            'education_level_group_id' => $educationLevelGroupId,
            'room_id' => $roomId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'planned_starts_at' => $plannedStartsAt,
            'planned_ends_at' => $plannedEndsAt,
            'has_level_conflict' => false,
            'has_room_conflict' => false,
            'occurrence_kind' => $occurrenceKind,
            'occurrence_id' => $occurrenceId,
            'session_id' => $sessionId,
            'actual_income' => $actualIncome,
            'session_status' => $sessionStatus,
        ];
    }

    private function parseDate(?string $value): Carbon
    {
        if ($value === null || $value === '') {
            return Carbon::now();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return Carbon::now();
        }
    }
}
