<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassSessionRequest;
use App\Http\Requests\Admin\StoreSessionStudentRequest;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassSessionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/sessions/index', [
            'sessions' => ClassSession::query()
                ->with(['teacher:id,name', 'subject:id,name'])
                ->withCount('students')
                ->orderBy('starts_at')
                ->paginate(10),
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
        ]);
    }

    public function store(StoreClassSessionRequest $request): RedirectResponse
    {
        $session = ClassSession::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة الحصة الاستثنائية. أضف الطلاب الآن.']);

        return redirect()->route('admin.sessions.show', $session);
    }

    public function show(ClassSession $classSession): Response
    {
        $classSession->load(['teacher:id,name', 'subject:id,name', 'students:id,name,phone']);

        $attendeeIds = $classSession->students->pluck('id');

        return Inertia::render('admin/sessions/show', [
            'session' => [
                'id' => $classSession->id,
                'starts_at' => $classSession->starts_at->toIso8601String(),
                'ends_at' => $classSession->ends_at->toIso8601String(),
                'teacher' => ['name' => $classSession->teacher->name],
                'subject' => ['name' => $classSession->subject->name],
                'students' => $classSession->students->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'phone' => $student->phone,
                ])->values(),
            ],
            'availableStudents' => Student::query()
                ->whereNotIn('id', $attendeeIds)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
        ]);
    }

    public function storeStudent(StoreSessionStudentRequest $request, ClassSession $classSession): RedirectResponse
    {
        $student = $this->resolveStudent($request->validated());

        $classSession->students()->syncWithoutDetaching([$student->id]);

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
