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
        $user = Auth::user();

        $query = Ticket::with(['billingInstance', 'assignedTechnician']);

        // Strict scoping for technicians!
        if ($user->role === 'technician') {
            $query->where('assigned_technician_id', $user->id);
        } else {
            // Admin / Operator can filter by tenant, technician, status
            if ($request->filled('billing_instance_id')) {
                $query->where('billing_instance_id', $request->billing_instance_id);
            }
            if ($request->filled('technician_id')) {
                $query->where('assigned_technician_id', $request->technician_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('no_services', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

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

        // Aggregated Customer Directory List for Select Option in Modal
        $customersList = \App\Models\Customer::select(
                'no_services',
                'name as customer_name',
                'phone as customer_phone',
                'address as customer_address',
                'billing_node_id as billing_instance_id',
                'status',
                'package_name',
                'odp_name'
            )->latest('updated_at')->get();

        if ($customersList->isEmpty()) {
            $customersList = Ticket::select(
                    'no_services',
                    DB::raw('MAX(customer_name) as customer_name'),
                    DB::raw('MAX(customer_phone) as customer_phone'),
                    DB::raw('MAX(customer_address) as customer_address'),
                    DB::raw('MAX(billing_instance_id) as billing_instance_id')
                )
                ->whereNotNull('no_services')
                ->where('no_services', '!=', '')
                ->groupBy('no_services')
                ->get();
        }

        return view('dashboard', compact(
            'user',
            'tickets',
            'pendingCount',
            'processCount',
            'closeCount',
            'totalCount',
            'tenants',
            'technicians',
            'customersList',
            'trendLabels',
            'trendCreated',
            'trendClosed'
        ));
    }
}
