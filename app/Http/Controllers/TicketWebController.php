<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\BillingInstance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketWebController extends Controller
{
    /**
     * Store a new ticket created manually by admin/operator
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'technician') {
            abort(403, 'Teknisi tidak dapat membuat tiket baru.');
        }

        $validated = $request->validate([
            'billing_instance_id' => 'required|exists:billing_instances,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'no_services' => 'required|string|max:100',
            'category_name' => 'nullable|string|max:100',
            'problem_description' => 'required|string',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
        ]);

        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'billing_instance_id' => $validated['billing_instance_id'],
            'no_services' => $validated['no_services'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'category_name' => $validated['category_name'] ?? null,
            'problem_description' => $validated['problem_description'],
            'status' => 'pending',
            'created_by_name' => $user->name,
            'created_by_role' => $user->role,
        ]);

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'remark' => "Tiket dibuat manual oleh {$user->name} ({$user->role})",
        ]);

        // Webhook callback to CI Billing Instance (e.g. billingtest.gayuh.net.id.test)
        $callbackUrl = $this->resolveCallbackUrl($ticket->billingInstance);
        if ($callbackUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(5)
                    ->post($callbackUrl, [
                        'event'               => 'ticket_created',
                        'ticket_number'       => $ticket->ticket_number,
                        'remote_ticket_id'    => $ticket->remote_ticket_id ?? $ticket->ticket_number,
                        'no_services'         => $ticket->no_services,
                        'customer_name'       => $ticket->customer_name,
                        'customer_phone'      => $ticket->customer_phone,
                        'status'              => $ticket->status,
                        'category_name'       => $ticket->category_name,
                        'problem_description' => $ticket->problem_description,
                        'created_by_name'     => $user->name,
                        'created_by_role'     => $user->role,
                        'updated_by_name'     => $user->name,
                        'updated_by_role'     => $user->role,
                    ]);

                if ($response->successful()) {
                    $resData = $response->json();
                    if (!empty($resData['help_id'])) {
                        $ticket->update(['remote_ticket_id' => $resData['help_id']]);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("New ticket webhook callback failed for {$ticket->billingInstance->name} (HTTP {$response->status()}): " . $response->body());
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("New ticket webhook callback failed for {$ticket->billingInstance->name}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Tiket {$ticketNumber} berhasil dibuat dan disinkronkan ke billing.");
    }

    /**
     * Display the specified ticket details.
     *
     * @param string|int $id
     * @return \Illuminate\View\View
     */
    public function show(string|int $id)
    {
        $user = Auth::user();

        $ticket = Ticket::with(['billingInstance', 'assignedTechnician', 'timelines.user'])
            ->findOrFail($id);

        // Security check for technician role
        if ($user->role === 'technician' && $ticket->assigned_technician_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $technicians = User::where('role', 'technician')->where('is_active', true)->get();

        return view('tickets.show', compact('ticket', 'user', 'technicians'));
    }

    /**
     * Assign a technician to the specified ticket.
     *
     * @param Request $request
     * @param string|int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignTechnician(Request $request, string|int $id)
    {
        $user = Auth::user();

        if ($user->role === 'technician') {
            abort(403, 'Teknisi tidak dapat merubah penugasan teknisi.');
        }

        $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($id);
        $technician = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_technician_id' => $technician->id,
        ]);

        $remark = "Ditugaskan ke teknisi: {$technician->name} oleh {$user->name} ({$user->role})";

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'status' => $ticket->status,
            'remark' => $remark,
        ]);

        // Webhook callback to CI Billing Instance
        $callbackUrl = $this->resolveCallbackUrl($ticket->billingInstance);
        if ($callbackUrl) {
            try {
                \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(5)
                    ->post($callbackUrl, [
                        'event'            => 'technician_assigned',
                        'ticket_number'    => $ticket->ticket_number,
                        'remote_ticket_id' => $ticket->remote_ticket_id ?? $ticket->ticket_number,
                        'status'           => $ticket->status,
                        'remark'           => $remark,
                        'technician_name'  => $technician->name,
                        'updated_by_name'  => $user->name,
                        'updated_by_role'  => $user->role,
                    ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Assign technician callback failed: " . $e->getMessage());
            }
        }

        return back()->with('success', "Tiket berhasil ditugaskan ke Teknisi {$technician->name}.");
    }

    /**
     * Update ticket status and trigger webhook callback.
     *
     * @param Request $request
     * @param string|int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, string|int $id)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role === 'technician' && $ticket->assigned_technician_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,process,close',
            'remark' => 'required|string',
        ]);

        $ticket->update([
            'status' => $validated['status'],
        ]);

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'status' => $validated['status'],
            'remark' => $validated['remark'],
        ]);

        // Webhook callback to CI Billing Instance
        $callbackUrl = $this->resolveCallbackUrl($ticket->billingInstance);
        if ($callbackUrl) {
            try {
                \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(5)
                    ->post($callbackUrl, [
                        'event'            => 'status_updated',
                        'ticket_number'    => $ticket->ticket_number,
                        'remote_ticket_id' => $ticket->remote_ticket_id ?? $ticket->ticket_number,
                        'status'           => $validated['status'],
                        'remark'           => $validated['remark'],
                        'technician_name'  => $user->name,
                        'updated_by_name'  => $user->name,
                        'updated_by_role'  => $user->role,
                    ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Callback failed: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Status tiket berhasil diperbarui & disinkronkan ke billing.');
    }

    /**
     * Resolve the webhook callback URL for a billing instance
     */
    private function resolveCallbackUrl($billingInstance): ?string
    {
        if (!$billingInstance) return null;

        $url = $billingInstance->callback_url;
        if (empty($url) || str_contains($url, '/help/api_callback')) {
            $domain = rtrim($billingInstance->domain_url, '/');
            return !empty($domain) ? "{$domain}/central/callback" : null;
        }

        return $url;
    }
}
