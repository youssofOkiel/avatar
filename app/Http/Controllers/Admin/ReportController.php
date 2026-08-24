<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        $sessions = ClassSession::query()
            ->withRecordedOutcome()
            ->whereBetween('starts_at', [$from, $to])
            ->get();

        $expenses = Expense::query()
            ->whereBetween('date', [$from, $to])
            ->get();

        /** @var array<string, array{date: string, sessions: int, attendance: int, income: float, expenses: float}> $days */
        $days = [];

        $ensureDay = function (string $date) use (&$days): void {
            if (! isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'sessions' => 0,
                    'attendance' => 0,
                    'income' => 0.0,
                    'expenses' => 0.0,
                ];
            }
        };

        foreach ($sessions as $session) {
            $date = $session->starts_at->toDateString();
            $ensureDay($date);
            $days[$date]['sessions']++;
            $days[$date]['income'] += (float) $session->income;
            $days[$date]['attendance'] += (int) ($session->attendance_count ?? 0);
        }

        foreach ($expenses as $expense) {
            $date = $expense->date->toDateString();
            $ensureDay($date);
            $days[$date]['expenses'] += (float) $expense->amount;
        }

        $rows = collect($days)
            ->map(function (array $day): array {
                $day['net'] = $day['income'] - $day['expenses'];

                return $day;
            })
            ->sortByDesc('date')
            ->values();

        return Inertia::render('admin/reports/index', [
            'rows' => $rows,
            'totals' => [
                'sessions' => (int) $rows->sum('sessions'),
                'attendance' => (int) $rows->sum('attendance'),
                'income' => (float) $rows->sum('income'),
                'expenses' => (float) $rows->sum('expenses'),
                'net' => (float) $rows->sum('net'),
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
