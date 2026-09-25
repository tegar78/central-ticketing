<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketTimeline;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Teknisi tidak memiliki akses ke log aktivitas sistem
        if ($user->role === 'technician') {
            abort(403, 'Akses ditolak. Teknisi tidak memiliki akses ke log aktivitas sistem.');
        }

        $query = TicketTimeline::with(['ticket.billingInstance', 'ticket.assignedTechnician', 'user'])
            ->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('remark', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('ticket', function ($tq) use ($search) {
                      $tq->where('ticket_number', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $activities = $query->paginate(20)->withQueryString();

        return view('activities.index', compact('activities', 'user'));
    }
}
