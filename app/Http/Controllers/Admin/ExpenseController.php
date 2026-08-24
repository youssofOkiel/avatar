<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/expenses/index', [
            'expenses' => Expense::query()
                ->latest('date')
                ->latest('id')
                ->paginate(10),
            'total' => (float) Expense::query()->sum('amount'),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمت إضافة المصروف.']);

        return redirect()->route('admin.expenses.index');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف المصروف.']);

        return redirect()->route('admin.expenses.index');
    }
}
