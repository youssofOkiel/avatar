<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveSessionOutcomeRequest;
use App\Http\Requests\Admin\StoreClassSessionRequest;
use App\Http\Requests\Admin\StoreSessionStudentRequest;
use App\Http\Requests\Admin\UpdateSessionAttendanceRequest;
use App\Models\ClassSession;
use App\Models\ExternalLecturerSchedule;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSchedule;
use App\Services\SessionOccurrenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClassSessionController extends Controller
{
    public function __construct(private SessionOccurrenceService $occurrences) {}

    public function index(Request $request): Response
    {
        $filter = $request->query('filter', 'all');

        $query = ClassSession::query()
            ->with(['teacher:id,name', 'subject:id,name', 'room:id,name']);

        if ($filter === 'pending') {
            $query->pendingOutcome();
        }

        $sessions = $query
            ->orderByDesc('starts_at')
            ->paginate(10)
            ->withQueryString();

        $sessions->getCollection()->transform(fn (ClassSession $session): array => $this->sessionListItem($session));

        return Inertia::render('admin/sessions/index', [
            'sessions' => $sessions,
            'filter' => $filter,
            'teachers' => Teacher::query()
                ->active()
                ->with('subjects:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Teacher $teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'subjects' => $teacher->subjects->map(fn ($subject): array => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ])->values(),
                ]),
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function resolveOutcome(ResolveSessionOutcomeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->startOfDay();

        $session = match ($validated['kind']) {
            'teacher' => $this->occurrences->findOrCreateForTeacherSchedule(
                TeacherSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'external' => $this->occurrences->findOrCreateForExternalSchedule(
                ExternalLecturerSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'session' => $this->occurrences->findOrCreateForExistingSession(
                ClassSession::query()->findOrFail($validated['id']),
            ),
        };

        return redirect()->route('admin.sessions.show', $session);
    }

    public function cancelOccurrence(ResolveSessionOutcomeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->startOfDay();

        $session = match ($validated['kind']) {
            'teacher' => $this->occurrences->findOrCreateForTeacherSchedule(
                TeacherSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'external' => $this->occurrences->findOrCreateForExternalSchedule(
                ExternalLecturerSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'session' => $this->occurrences->findOrCreateForExistingSession(
                ClassSession::query()->findOrFail($validated['id']),
            ),
        };

        $this->markCanceled($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم إلغاء الحصة.']);

        return redirect()->route('admin.schedule.index', ['date' => $validated['date']]);
    }

    public function cancel(ClassSession $classSession): RedirectResponse
    {
        if ($classSession->hasRecordedOutcome()) {
            return back()->withErrors([
                'session' => 'لا يمكن إلغاء حصة تم تسجيل إيرادها.',
            ]);
        }

        if ($classSession->isCanceled()) {
            return back()->withErrors([
                'session' => 'الحصة ملغاة بالفعل.',
            ]);
        }

        $this->markCanceled($classSession);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم إلغاء الحصة.']);

        if ($classSession->isPast()) {
            return redirect()->route('admin.sessions.index', ['filter' => 'pending']);
        }

        return redirect()->route('admin.sessions.show', $classSession);
    }

    public function restoreOccurrence(ResolveSessionOutcomeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->startOfDay();

        $session = match ($validated['kind']) {
            'teacher' => $this->occurrences->findOrCreateForTeacherSchedule(
                TeacherSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'external' => $this->occurrences->findOrCreateForExternalSchedule(
                ExternalLecturerSchedule::query()->findOrFail($validated['id']),
                $date,
            ),
            'session' => $this->occurrences->findOrCreateForExistingSession(
                ClassSession::query()->findOrFail($validated['id']),
            ),
        };

        if (! $session->isCanceled()) {
            return back()->withErrors([
                'session' => 'الحصة غير ملغاة.',
            ]);
        }

        $this->markRestored($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم استعادة الحصة.']);

        return redirect()->route('admin.schedule.index', ['date' => $validated['date']]);
    }

    public function restore(ClassSession $classSession): RedirectResponse
    {
        if (! $classSession->isCanceled()) {
            return back()->withErrors([
                'session' => 'الحصة غير ملغاة.',
            ]);
        }

        $this->markRestored($classSession);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم استعادة الحصة.']);

        return redirect()->route('admin.sessions.show', $classSession);
    }

    public function store(StoreClassSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $session = ClassSession::query()->create([
            'type' => $validated['type'],
            'room_id' => $validated['room_id'],
            'income' => 0,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'teacher_id' => $validated['type'] === 'subject' ? $validated['teacher_id'] : null,
            'subject_id' => $validated['type'] === 'subject' ? $validated['subject_id'] : null,
            'title' => $validated['type'] === 'rental' ? $validated['title'] : null,
            'attendance_count' => $validated['type'] === 'rental' ? ($validated['attendance_count'] ?? 0) : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة الحصة. سجّل الإيراد بعد انتهائها.']);

        return redirect()->route('admin.sessions.show', $session);
    }

    public function show(ClassSession $classSession): Response
    {
        $classSession->load(['teacher:id,name', 'subject:id,name', 'room:id,name', 'students:id,name,phone']);

        $attendeeIds = $classSession->students->pluck('id');

        return Inertia::render('admin/sessions/show', [
            'session' => $this->sessionDetail($classSession),
            'availableStudents' => Student::query()
                ->whereNotIn('id', $attendeeIds)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
        ]);
    }

    public function update(UpdateSessionAttendanceRequest $request, ClassSession $classSession): RedirectResponse
    {
        if (! $classSession->isPast()) {
            return back()->withErrors([
                'income' => 'لا يمكن تسجيل الإيراد قبل بدء الحصة.',
            ]);
        }

        $validated = $request->validated();

        $payload = [
            'income' => $validated['income'] ?? $classSession->income,
            'attendance_count' => array_key_exists('attendance_count', $validated)
                ? $validated['attendance_count']
                : $classSession->attendance_count,
            'outcome_recorded_at' => now(),
            'canceled_at' => null,
        ];

        if (! empty($validated['ends_at'])) {
            $payload['ends_at'] = $this->combineSessionDateAndTime(
                $classSession,
                (string) $validated['ends_at'],
            );
        }

        $classSession->update($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حفظ الإيراد الفعلي والحضور.']);

        return redirect()->route('admin.sessions.show', $classSession);
    }

    public function storeStudent(StoreSessionStudentRequest $request, ClassSession $classSession): RedirectResponse
    {
        $student = $this->resolveStudent($request->validated());

        $classSession->students()->syncWithoutDetaching([$student->id => ['attended' => false]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة الطالب إلى الحصة.']);

        return redirect()->route('admin.sessions.show', $classSession);
    }

    public function destroyStudent(ClassSession $classSession, Student $student): RedirectResponse
    {
        $classSession->students()->detach($student->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف الطالب من الحصة.']);

        return redirect()->route('admin.sessions.show', $classSession);
    }

    public function destroy(ClassSession $classSession): RedirectResponse
    {
        $classSession->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف الحصة.']);

        return redirect()->route('admin.sessions.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionListItem(ClassSession $session): array
    {
        return [
            'id' => $session->id,
            'type' => $session->type,
            'title' => $session->title,
            'teacher' => $session->teacher ? ['name' => $session->teacher->name] : null,
            'subject' => $session->subject ? ['name' => $session->subject->name] : null,
            'room' => $session->room ? ['name' => $session->room->name] : null,
            'income' => (float) $session->income,
            'starts_at' => $session->starts_at->toIso8601String(),
            'attendance' => $session->attendance_count,
            'outcome_recorded_at' => $session->outcome_recorded_at?->toIso8601String(),
            'canceled_at' => $session->canceled_at?->toIso8601String(),
            'is_past' => $session->isPast(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionDetail(ClassSession $session): array
    {
        return [
            'id' => $session->id,
            'type' => $session->type,
            'title' => $session->title,
            'income' => (float) $session->income,
            'attendance_count' => $session->attendance_count,
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at->toIso8601String(),
            'outcome_recorded_at' => $session->outcome_recorded_at?->toIso8601String(),
            'canceled_at' => $session->canceled_at?->toIso8601String(),
            'is_past' => $session->isPast(),
            'teacher' => $session->teacher ? ['name' => $session->teacher->name] : null,
            'subject' => $session->subject ? ['name' => $session->subject->name] : null,
            'room' => $session->room ? ['name' => $session->room->name] : null,
            'students' => $session->students->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'attended' => (bool) $student->pivot->attended,
            ])->values(),
        ];
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
            $student = Student::withTrashed()->firstOrNew(['phone' => $phone]);
            if ($student->trashed()) {
                $student->restore();
            }
            if ($name && ! $student->name) {
                $student->name = $name;
            }
            $student->save();

            return $student;
        }

        return Student::query()->create(['name' => $name]);
    }

    private function combineSessionDateAndTime(ClassSession $session, string $time): Carbon
    {
        $normalized = strlen($time) === 5 ? $time.':00' : $time;
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $normalized));

        return Carbon::instance($session->starts_at)->setTime($hours, $minutes, $seconds);
    }

    private function markCanceled(ClassSession $session): void
    {
        $session->update([
            'income' => 0,
            'outcome_recorded_at' => null,
            'canceled_at' => now(),
        ]);
    }

    private function markRestored(ClassSession $session): void
    {
        $session->update([
            'canceled_at' => null,
        ]);
    }
}
