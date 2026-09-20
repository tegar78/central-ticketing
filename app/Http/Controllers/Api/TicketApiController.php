<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\TicketTimeline;
use Illuminate\Support\Str;

class TicketApiController extends Controller
{
    /**
     * Ingest new ticket from CI billing instance
     */
    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'remote_ticket_id' => 'nullable|string',
            'no_services' => 'required|string',
            'customer_name' => 'required|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'category_name' => 'nullable|string',
            'problem_description' => 'required|string',
            'picture' => 'nullable|string',
            'created_by_name' => 'nullable|string',
            'created_by_role' => 'nullable|string',
        ]);

        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'billing_instance_id' => $tenant->id,
            'remote_ticket_id' => $validated['remote_ticket_id'] ?? null,
            'no_services' => $validated['no_services'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'category_name' => $validated['category_name'] ?? null,
            'problem_description' => $validated['problem_description'],
            'picture' => $validated['picture'] ?? null,
            'status' => 'pending',
            'created_by_name' => $validated['created_by_name'] ?? 'System',
            'created_by_role' => $validated['created_by_role'] ?? 'Client',
        ]);

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'status' => 'pending',
            'remark' => 'Tiket dibuat via ' . $tenant->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil diterima di Central System',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'tenant_code' => $tenant->tenant_code,
            ]
        ], 201);
    }

    /**
     * Get list of tickets for the calling tenant
     */
    public function list(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $tickets = Ticket::where('billing_instance_id', $tenant->id)
            ->with(['assignedTechnician:id,name,phone', 'timelines'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * Get detail of a specific ticket for the tenant
     */
    public function show(Request $request, $id)
    {
        $tenant = $request->attributes->get('tenant');

        $ticket = Ticket::where('billing_instance_id', $tenant->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('ticket_number', $id)
                  ->orWhere('remote_ticket_id', $id);
            })
            ->with(['assignedTechnician:id,name,phone', 'timelines.user:id,name,role'])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }
}
