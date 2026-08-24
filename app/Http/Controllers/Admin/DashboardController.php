<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Expense;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()?->isSuperadmin()) {
            return redirect()->route('admin.reservations.index');
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $totalIncome = (float) ClassSession::query()->withRecordedOutcome()->sum('income');
        $totalExpenses = (float) Expense::query()->sum('amount');
        $monthIncome = (float) ClassSession::query()
            ->withRecordedOutcome()
            ->whereBetween('starts_at', [$monthStart, $monthEnd])
            ->sum('income');
        $monthExpenses = (float) Expense::query()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        return Inertia::render('dashboard', [
            'stats' => [
                'teachers' => Teacher::query()->count(),
                'students' => Student::query()->count(),
                'reservations' => Reservation::query()->count(),
                'sessions' => ClassSession::query()->count(),
            ],
            'finance' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'total_net' => $totalIncome - $totalExpenses,
                'month_income' => $monthIncome,
                'month_expenses' => $monthExpenses,
                'month_net' => $monthIncome - $monthExpenses,
            ],
        ]);
    }
}
