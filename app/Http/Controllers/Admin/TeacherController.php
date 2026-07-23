<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\EducationLevel;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/teachers/index', [
            'teachers' => Teacher::query()
                ->with('subjects:id,name')
                ->withCount('reservations')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/teachers/form', [
            'teacher' => null,
            'levels' => $this->levelsWithSubjects(),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $teacher = Teacher::query()->create([
                'name' => $request->validated('name'),
                'bio' => $request->validated('bio'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncSelectionsAndSchedules($teacher, $request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة المعلم.']);

        return redirect()->route('admin.teachers.index');
    }

    public function show(Teacher $teacher): Response
    {
        $teacher->load([
            'teacherSubjects.subject:id,name',
            'teacherSubjects.educationLevel:id,name',
            'schedules',
            'reservations.student:id,name,phone',
            'reservations.teacherSchedules:id,day_of_week,starts_at,ends_at',
        ]);

        $teaching = $teacher->teacherSubjects->map(function ($item) use ($teacher): array {
            $schedules = $teacher->schedules
                ->where('education_level_id', $item->education_level_id)
                ->where('subject_id', $item->subject_id)
                ->sortBy([['day_of_week', 'asc'], ['starts_at', 'asc']])
                ->map(fn ($schedule): array => [
                    'day_of_week' => $schedule->day_of_week,
                    'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                    'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                ])->values();

            $reservations = $teacher->reservations
                ->where('education_level_id', $item->education_level_id)
                ->where('subject_id', $item->subject_id)
                ->map(fn ($reservation): array => [
                    'id' => $reservation->id,
                    'student_name' => $reservation->student->name,
                    'student_phone' => $reservation->student->phone,
                    'slots' => $reservation->teacherSchedules->map(fn ($slot): array => [
                        'day_of_week' => $slot->day_of_week,
                        'starts_at' => substr((string) $slot->starts_at, 0, 5),
                        'ends_at' => substr((string) $slot->ends_at, 0, 5),
                    ])->values(),
                ])->values();

            return [
                'education_level' => [
                    'id' => $item->education_level_id,
                    'name' => $item->educationLevel->name,
                ],
                'subject' => [
                    'id' => $item->subject_id,
                    'name' => $item->subject->name,
                ],
                'schedules' => $schedules,
                'reservations' => $reservations,
            ];
        })->values();

        return Inertia::render('admin/teachers/show', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'bio' => $teacher->bio,
                'is_active' => $teacher->is_active,
                'reservations_count' => $teacher->reservations->count(),
            ],
            'teaching' => $teaching,
        ]);
    }

    public function edit(Teacher $teacher): Response
    {
        $teacher->load(['teacherSubjects', 'schedules']);

        return Inertia::render('admin/teachers/form', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'bio' => $teacher->bio,
                'is_active' => $teacher->is_active,
                'selections' => $teacher->teacherSubjects->map(fn ($item): array => [
                    'education_level_id' => $item->education_level_id,
                    'subject_id' => $item->subject_id,
                ]),
                'schedules' => $teacher->schedules->map(fn ($schedule): array => [
                    'education_level_id' => $schedule->education_level_id,
                    'subject_id' => $schedule->subject_id,
                    'day_of_week' => $schedule->day_of_week,
                    'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                    'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                ]),
            ],
            'levels' => $this->levelsWithSubjects(),
        ]);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        DB::transaction(function () use ($request, $teacher): void {
            $teacher->update([
                'name' => $request->validated('name'),
                'bio' => $request->validated('bio'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncSelectionsAndSchedules($teacher, $request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تحديث بيانات المعلم.']);

        return redirect()->route('admin.teachers.index');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المعلم.']);

        return redirect()->route('admin.teachers.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncSelectionsAndSchedules(Teacher $teacher, array $validated): void
    {
        $selections = $validated['selections'] ?? [];

        $teacher->teacherSubjects()->delete();

        $selectedKeys = [];
        foreach ($selections as $selection) {
            $teacher->teacherSubjects()->create([
                'education_level_id' => $selection['education_level_id'],
                'subject_id' => $selection['subject_id'],
            ]);
            $selectedKeys[] = $selection['education_level_id'].':'.$selection['subject_id'];
        }

        $teacher->schedules()->delete();

        foreach ($validated['schedules'] ?? [] as $schedule) {
            $key = $schedule['education_level_id'].':'.$schedule['subject_id'];
            if (! in_array($key, $selectedKeys, true)) {
                continue;
            }

            $teacher->schedules()->create([
                'education_level_id' => $schedule['education_level_id'],
                'subject_id' => $schedule['subject_id'],
                'day_of_week' => $schedule['day_of_week'],
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
            ]);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function levelsWithSubjects(): \Illuminate\Support\Collection
    {
        return EducationLevel::query()
            ->with('subjects:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (EducationLevel $level): array => [
                'id' => $level->id,
                'name' => $level->name,
                'subjects' => $level->subjects->map(fn ($subject): array => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ])->values(),
            ]);
    }
}
