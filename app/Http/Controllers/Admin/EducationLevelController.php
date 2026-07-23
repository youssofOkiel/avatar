<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEducationLevelRequest;
use App\Http\Requests\Admin\UpdateEducationLevelRequest;
use App\Models\EducationLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EducationLevelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/education-levels/index', [
            'levels' => EducationLevel::query()->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/education-levels/form', [
            'level' => null,
        ]);
    }

    public function store(StoreEducationLevelRequest $request): RedirectResponse
    {
        EducationLevel::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة المرحلة الدراسية.']);

        return redirect()->route('admin.education-levels.index');
    }

    public function edit(EducationLevel $educationLevel): Response
    {
        return Inertia::render('admin/education-levels/form', [
            'level' => $educationLevel,
        ]);
    }

    public function update(UpdateEducationLevelRequest $request, EducationLevel $educationLevel): RedirectResponse
    {
        $educationLevel->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تحديث المرحلة الدراسية.']);

        return redirect()->route('admin.education-levels.index');
    }

    public function destroy(EducationLevel $educationLevel): RedirectResponse
    {
        $educationLevel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المرحلة الدراسية.']);

        return redirect()->route('admin.education-levels.index');
    }
}
