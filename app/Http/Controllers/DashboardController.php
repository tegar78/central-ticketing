<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\BillingInstance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // If status or search is queried on dashboard, redirect cleanly to tickets directory
        if ($request->filled('status') || $request->filled('search')) {
            return redirect()->route('tickets.index', $request->query());
        }

        $user = Auth::user();

        // Calculate counts
        $baseCountQuery = Ticket::query();
        if ($user->role === 'technician') {
            $baseCountQuery->where('assigned_technician_id', $user->id);
        }

        $pendingCount = (clone $baseCountQuery)->where('status', 'pending')->count();
        $processCount = (clone $baseCountQuery)->where('status', 'process')->count();
        $closeCount = (clone $baseCountQuery)->where('status', 'close')->count();
        $totalCount = (clone $baseCountQuery)->count();

        // Weekly Activity Trend Data (Last 7 days)
        $startDate = \Carbon\Carbon::today()->subDays(6)->startOfDay();
        $endDate = \Carbon\Carbon::today()->endOfDay();

        $createdCountsQuery = Ticket::select(DB::raw('DATE(created_at) as date_val'), DB::raw('count(*) as count_val'))
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($user->role === 'technician') {
            $createdCountsQuery->where('assigned_technician_id', $user->id);
        }
        $createdCounts = $createdCountsQuery->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count_val', 'date_val');

        $closedTimelineQuery = \App\Models\TicketTimeline::select(DB::raw('DATE(created_at) as date_val'), DB::raw('count(distinct ticket_id) as count_val'))
            ->where('status', 'close')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($user->role === 'technician') {
            $closedTimelineQuery->whereHas('ticket', function ($q) use ($user) {
                $q->where('assigned_technician_id', $user->id);
            });
        }
        $closedTimelineCounts = $closedTimelineQuery->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count_val', 'date_val');

        $fallbackClosedQuery = Ticket::select(DB::raw('DATE(updated_at) as date_val'), DB::raw('count(*) as count_val'))
            ->where('status', 'close')
            ->whereBetween('updated_at', [$startDate, $endDate]);

        if ($user->role === 'technician') {
            $fallbackClosedQuery->where('assigned_technician_id', $user->id);
        }
        $fallbackClosedCounts = $fallbackClosedQuery->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('count_val', 'date_val');

        $dayNames = [
            'Sun' => 'Min',
            'Mon' => 'Sen',
            'Tue' => 'Sel',
            'Wed' => 'Rab',
            'Thu' => 'Kam',
            'Fri' => 'Jum',
            'Sat' => 'Sab',
        ];

        $trendLabels = [];
        $trendCreated = [];
        $trendClosed = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $dayName = $dayNames[$date->format('D')] ?? $date->format('D');

            $trendLabels[] = $dayName . ' (' . $date->format('d/m') . ')';
            $trendCreated[] = (int) ($createdCounts[$dateKey] ?? 0);

            $closedVal = max(
                (int) ($closedTimelineCounts[$dateKey] ?? 0),
                (int) ($fallbackClosedCounts[$dateKey] ?? 0)
            );
            $trendClosed[] = $closedVal;
        }

        $tenants = BillingInstance::where('is_active', true)->get();
        $technicians = User::where('role', 'technician')->where('is_active', true)->get();
        $totalCustomersCount = \App\Models\Customer::count();

        return view('dashboard', compact(
            'user',
            'pendingCount',
            'processCount',
            'closeCount',
            'totalCount',
            'tenants',
            'technicians',
            'totalCustomersCount',
            'trendLabels',
            'trendCreated',
            'trendClosed'
        ));
    }
}
