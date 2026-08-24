<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Subject;
use App\Support\GroupedEducationLevels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $levelId = $request->integer('level') ?: null;

        return Inertia::render('admin/subjects/index', [
            'subjects' => Subject::query()
                ->with('educationLevels')
                ->select('subjects.*')
                ->selectSub(
                    DB::table('teacher_subject')
                        ->selectRaw('count(distinct teacher_id)')
                        ->whereColumn('teacher_subject.subject_id', 'subjects.id'),
                    'teachers_count'
                )
                ->when($levelId, fn ($query) => $query->whereHas(
                    'educationLevels',
                    fn ($levelQuery) => $levelQuery->where('education_levels.id', $levelId)
                ))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'levelGroups' => GroupedEducationLevels::grouped(),
            'filters' => ['level' => $levelId],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/subjects/form', [
            'subject' => null,
            'levelGroups' => GroupedEducationLevels::grouped(),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::query()->create([
            'name' => $request->validated('name'),
        ]);

        $subject->educationLevels()->sync($request->validated('education_level_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة المادة.']);

        return redirect()->route('admin.subjects.index');
    }

    public function edit(Subject $subject): Response
    {
        $subject->load('educationLevels');

        return Inertia::render('admin/subjects/form', [
            'subject' => $subject,
            'levelGroups' => GroupedEducationLevels::grouped(),
        ]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update([
            'name' => $request->validated('name'),
        ]);

        $subject->educationLevels()->sync($request->validated('education_level_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم تحديث المادة.']);

        return redirect()->route('admin.subjects.index');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المادة.']);

        return redirect()->route('admin.subjects.index');
    }
}
