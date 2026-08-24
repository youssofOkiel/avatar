<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExternalLecturerRequest;
use App\Http\Requests\Admin\UpdateExternalLecturerRequest;
use App\Models\ExternalLecturer;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExternalLecturerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/external-lecturers/index', [
            'lecturers' => ExternalLecturer::query()
                ->withCount('schedules')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/external-lecturers/form', [
            'lecturer' => null,
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreExternalLecturerRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $lecturer = ExternalLecturer::query()->create([
                'name' => $request->validated('name'),
            ]);

            $this->syncSchedules($lecturer, $request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة المحاضر.']);

        return redirect()->route('admin.external-lecturers.index');
    }

    public function show(ExternalLecturer $externalLecturer): Response
    {
        $externalLecturer->load('schedules.room:id,name');

        $schedules = $externalLecturer->schedules
            ->sortBy([['day_of_week', 'asc'], ['starts_at', 'asc']])
            ->map(fn ($schedule): array => [
                'topic' => $schedule->topic,
                'day_of_week' => $schedule->day_of_week,
                'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                'room' => $schedule->room?->name,
                'income' => (float) $schedule->income,
            ])->values();

        return Inertia::render('admin/external-lecturers/show', [
            'lecturer' => [
                'id' => $externalLecturer->id,
                'name' => $externalLecturer->name,
            ],
            'schedules' => $schedules,
        ]);
    }

    public function edit(ExternalLecturer $externalLecturer): Response
    {
        $externalLecturer->load('schedules');

        return Inertia::render('admin/external-lecturers/form', [
            'lecturer' => [
                'id' => $externalLecturer->id,
                'name' => $externalLecturer->name,
                'schedules' => $externalLecturer->schedules->map(fn ($schedule): array => [
                    'topic' => $schedule->topic,
                    'room_id' => $schedule->room_id,
                    'day_of_week' => $schedule->day_of_week,
                    'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                    'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                    'income' => (float) $schedule->income,
                ]),
            ],
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateExternalLecturerRequest $request, ExternalLecturer $externalLecturer): RedirectResponse
    {
        DB::transaction(function () use ($request, $externalLecturer): void {
            $externalLecturer->update([
                'name' => $request->validated('name'),
            ]);

            $this->syncSchedules($externalLecturer, $request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تحديث بيانات المحاضر.']);

        return redirect()->route('admin.external-lecturers.index');
    }

    public function destroy(ExternalLecturer $externalLecturer): RedirectResponse
    {
        DB::transaction(function () use ($externalLecturer): void {
            $externalLecturer->schedules()->delete();
            $externalLecturer->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المحاضر.']);

        return redirect()->route('admin.external-lecturers.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncSchedules(ExternalLecturer $lecturer, array $validated): void
    {
        $lecturer->schedules()->delete();

        foreach ($validated['schedules'] ?? [] as $schedule) {
            $lecturer->schedules()->create([
                'topic' => $schedule['topic'],
                'room_id' => $schedule['room_id'] ?? null,
                'day_of_week' => $schedule['day_of_week'],
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
                'income' => $schedule['income'] ?? 0,
            ]);
        }
    }
}
