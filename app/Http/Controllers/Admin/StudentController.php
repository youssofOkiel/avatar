<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/students/index', [
            'students' => Student::query()
                ->withCount('reservations')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/students/form', [
            'student' => null,
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Student::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة الطالب.']);

        return redirect()->route('admin.students.index');
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('admin/students/form', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تحديث بيانات الطالب.']);

        return redirect()->route('admin.students.index');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف الطالب.']);

        return redirect()->route('admin.students.index');
    }
}
