<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Models\EducationLevel;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\TeacherSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/reservations/index', [
            'reservations' => Reservation::query()
                ->with([
                    'student:id,name,phone',
                    'subject:id,name',
                    'teacher:id,name',
                    'educationLevel:id,name',
                    'teacherSchedules:id,day_of_week,starts_at,ends_at',
                ])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/reservations/create', [
            'students' => Student::query()
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
            'levels' => EducationLevel::query()
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
                ]),
            'teacherSubjects' => TeacherSubject::query()
                ->whereHas('teacher', fn ($query) => $query->where('is_active', true))
                ->with('teacher:id,name')
                ->get()
                ->map(fn (TeacherSubject $item): array => [
                    'teacher_id' => $item->teacher_id,
                    'teacher_name' => $item->teacher->name,
                    'education_level_id' => $item->education_level_id,
                    'subject_id' => $item->subject_id,
                ]),
            'schedules' => TeacherSchedule::query()
                ->get()
                ->map(fn (TeacherSchedule $schedule): array => [
                    'id' => $schedule->id,
                    'teacher_id' => $schedule->teacher_id,
                    'education_level_id' => $schedule->education_level_id,
                    'subject_id' => $schedule->subject_id,
                    'day_of_week' => $schedule->day_of_week,
                    'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                    'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                ]),
        ]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $student = $this->resolveStudent($request->validated());

            $reservation = Reservation::query()->firstOrCreate([
                'student_id' => $student->id,
                'education_level_id' => $request->validated('education_level_id'),
                'subject_id' => $request->validated('subject_id'),
                'teacher_id' => $request->validated('teacher_id'),
            ]);

            $scheduleIds = TeacherSchedule::query()
                ->where('teacher_id', $request->validated('teacher_id'))
                ->where('education_level_id', $request->validated('education_level_id'))
                ->where('subject_id', $request->validated('subject_id'))
                ->whereIn('id', $request->validated('teacher_schedule_ids') ?? [])
                ->pluck('id');

            $reservation->teacherSchedules()->sync($scheduleIds);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تسجيل الحجز.']);

        return redirect()->route('admin.reservations.index');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف الحجز.']);

        return redirect()->route('admin.reservations.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveStudent(array $validated): Student
    {
        if (! empty($validated['student_id'])) {
            return Student::query()->findOrFail($validated['student_id']);
        }

        $name = $validated['name'] ?? null;
        $phone = isset($validated['phone']) ? trim((string) $validated['phone']) : null;

        if ($phone) {
            $student = Student::query()->firstOrNew(['phone' => $phone]);
            if ($name && ! $student->name) {
                $student->name = $name;
            }
            $student->save();

            return $student;
        }

        return Student::query()->create(['name' => $name]);
    }
}
