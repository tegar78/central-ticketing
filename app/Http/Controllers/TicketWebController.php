<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketStatusRequest;
use App\Jobs\DispatchBillingWebhookJob;
use App\Jobs\SendTicketTelegramNotificationJob;
use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketWebController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Display centralized tickets directory with search, filters & export options
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Ticket::with(['billingInstance', 'assignedTechnician']);

        // Role scoping: technician only sees assigned tickets
        if ($user->role === UserRole::Technician->value) {
            $query->where('assigned_technician_id', $user->id);
        } else {
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

        // Calculate counts for quick status navigation pills
        $baseCountQuery = Ticket::query();
        if ($user->role === UserRole::Technician->value) {
            $baseCountQuery->where('assigned_technician_id', $user->id);
        }

        $pendingCount = (clone $baseCountQuery)->where('status', TicketStatus::Pending->value)->count();
        $processCount = (clone $baseCountQuery)->where('status', TicketStatus::Process->value)->count();
        $closeCount = (clone $baseCountQuery)->where('status', TicketStatus::Close->value)->count();
        $totalCount = (clone $baseCountQuery)->count();

        $tenants = BillingInstance::where('is_active', true)->withCount('customers')->get();
        $technicians = User::where('role', UserRole::Technician->value)->where('is_active', true)->get();
        $totalCustomersCount = Customer::count();

        return view('tickets.index', compact(
            'user',
            'tickets',
            'pendingCount',
            'processCount',
            'closeCount',
            'totalCount',
            'tenants',
            'technicians',
            'totalCustomersCount'
        ));
    }

    /**
     * Store a new ticket created manually by admin/operator
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === UserRole::Technician->value) {
            abort(403, 'Teknisi tidak dapat membuat tiket baru.');
        }

        $validated = $request->validated();
        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $ticket = DB::transaction(function () use ($validated, $ticketNumber, $user, $request) {
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
                'status' => TicketStatus::Pending->value,
                'created_by_name' => $user->name,
                'created_by_role' => $user->role,
            ]);

            $deviceInfo = $this->getClientDeviceInfo($request);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'status' => TicketStatus::Pending->value,
                'remark' => "Tambah Tiket Gangguan dari {$deviceInfo}",
            ]);

            return $ticket;
        });

        // Webhook callback to CI Billing Instance dispatched via Queue
        if ($ticket->billingInstance) {
            DispatchBillingWebhookJob::dispatch($ticket->billingInstance, [
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
            ], $ticket->id);
        }

        // Kirim Notifikasi ke Grup Telegram secara langsung
        SendTicketTelegramNotificationJob::dispatchSync($ticket, 'ticket_created', $ticket->problem_description, $user);

        return back()->with('success', "Tiket {$ticketNumber} berhasil dibuat dan disinkronkan ke billing.");
    }

    /**
     * Display the specified ticket details.
     */
    public function show(string|int $id): View
    {
        $user = Auth::user();

        $ticket = Ticket::with(['billingInstance', 'assignedTechnician', 'timelines.user'])
            ->findOrFail($id);

        // Security check for technician role
        if ($user->role === UserRole::Technician->value && $ticket->assigned_technician_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $technicians = User::where('role', UserRole::Technician->value)->where('is_active', true)->get();

        return view('tickets.show', compact('ticket', 'user', 'technicians'));
    }

    /**
     * Assign a technician to the specified ticket.
     */
    public function assignTechnician(Request $request, string|int $id): RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === UserRole::Technician->value) {
            abort(403, 'Teknisi tidak dapat merubah penugasan teknisi.');
        }

        $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($ticket->isClosed()) {
            return back()->with('error', 'Tiket ini telah berstatus Selesai (Closed) dan terkunci. Penugasan teknisi tidak dapat diubah lagi.');
        }

        $technician = User::findOrFail($request->technician_id);
        $deviceInfo = $this->getClientDeviceInfo($request);
        $remark = "Ditugaskan ke teknisi: {$technician->name} dari {$deviceInfo}";

        DB::transaction(function () use ($ticket, $technician, $user, $remark) {
            $ticket->update([
                'assigned_technician_id' => $technician->id,
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'status' => $ticket->status,
                'remark' => $remark,
            ]);
        });

        // Webhook callback to CI Billing Instance via Queue
        if ($ticket->billingInstance) {
            DispatchBillingWebhookJob::dispatch($ticket->billingInstance, [
                'event'            => 'technician_assigned',
                'ticket_number'    => $ticket->ticket_number,
                'remote_ticket_id' => $ticket->remote_ticket_id ?? $ticket->ticket_number,
                'status'           => $ticket->status,
                'remark'           => $remark,
                'technician_name'  => $technician->name,
                'updated_by_name'  => $user->name,
                'updated_by_role'  => $user->role,
            ]);
        }

        // Kirim Notifikasi ke Grup Telegram secara langsung
        SendTicketTelegramNotificationJob::dispatchSync($ticket, 'technician_assigned', $remark, $user);

        return back()->with('success', "Tiket berhasil ditugaskan ke Teknisi {$technician->name}.");
    }

    /**
     * Update ticket status and trigger webhook callback.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, string|int $id): RedirectResponse
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($ticket->isClosed()) {
            return back()->with('error', 'Tiket ini telah berstatus Selesai (Closed) dan terkunci. Status tidak dapat diperbarui lagi.');
        }

        if ($user->role === UserRole::Technician->value && $ticket->assigned_technician_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $validated = $request->validated();
        $deviceInfo = $this->getClientDeviceInfo($request);
        $statusEnum = $validated['status'] instanceof TicketStatus ? $validated['status'] : TicketStatus::from((string) $validated['status']);
        $remarkText = "Ubah status ke {$statusEnum->label()}: {$validated['remark']} dari {$deviceInfo}";

        DB::transaction(function () use ($ticket, $statusEnum, $user, $remarkText) {
            $ticket->update([
                'status' => $statusEnum->value,
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'status' => $statusEnum->value,
                'remark' => $remarkText,
            ]);
        });

        // Webhook callback to CI Billing Instance via Queue
        if ($ticket->billingInstance) {
            DispatchBillingWebhookJob::dispatch($ticket->billingInstance, [
                'event'            => 'status_updated',
                'ticket_number'    => $ticket->ticket_number,
                'remote_ticket_id' => $ticket->remote_ticket_id ?? $ticket->ticket_number,
                'status'           => $statusEnum->value,
                'remark'           => $validated['remark'],
                'technician_name'  => $user->name,
                'updated_by_name'  => $user->name,
                'updated_by_role'  => $user->role,
            ]);
        }

        // Kirim Notifikasi ke Grup Telegram secara langsung
        SendTicketTelegramNotificationJob::dispatchSync($ticket, 'status_updated', $validated['remark'], $user);

        return back()->with('success', 'Status tiket berhasil diperbarui & disinkronkan ke billing.');
    }

    /**
     * Get formatted client OS, IP address, and browser matching Billing reference
     */
    protected function getClientDeviceInfo(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        $ip = $request->ip() ?? '127.0.0.1';

        $os = 'Windows';
        if (str_contains($ua, 'Windows NT 10.0')) $os = 'Windows 10';
        elseif (str_contains($ua, 'Windows NT 11.0')) $os = 'Windows 11';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
        elseif (str_contains($ua, 'Macintosh')) $os = 'MacOS';
        elseif (str_contains($ua, 'Linux')) $os = 'Linux';

        $browser = 'Chrome 153.0.0.0';
        if (preg_match('/Chrome\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Chrome ' . $matches[1];
        } elseif (preg_match('/Edg\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Edge ' . $matches[1];
        } elseif (preg_match('/Firefox\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Firefox ' . $matches[1];
        } elseif (preg_match('/Safari\/([0-9\.]+)/i', $ua, $matches)) {
            $browser = 'Safari ' . $matches[1];
        }

        return "{$os} {$ip} {$browser}";
    }

    /**
     * Resolve the webhook callback URL for a billing instance
     */
    private function resolveCallbackUrl(?BillingInstance $billingInstance): ?string
    {
        if (!$billingInstance) return null;

        $url = $billingInstance->callback_url;
        if (empty($url) || str_contains($url, '/help/api_callback')) {
            $domain = rtrim($billingInstance->domain_url, '/');
            return !empty($domain) ? "{$domain}/central/callback" : null;
        }

        return $url;
    }

    /**
     * Dispatch cryptographically signed webhook callback to a billing instance
     */
    protected function dispatchBillingWebhook(?BillingInstance $instance, array $payload): ?\Illuminate\Http\Client\Response
    {
        $callbackUrl = $this->resolveCallbackUrl($instance);
        if (!$instance || !$callbackUrl) {
            return null;
        }

        try {
            $timestamp = time();
            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', "{$timestamp}.{$payloadJson}", $instance->api_key ?? '');

            $verifySsl = (bool) env('BILLING_VERIFY_SSL', false);
            $client = $verifySsl
                ? \Illuminate\Support\Facades\Http::timeout(5)
                : \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(5);

            $response = $client->withHeaders([
                'X-Central-Signature' => $signature,
                'X-Central-Timestamp' => (string) $timestamp,
                'X-API-KEY'           => $instance->api_key,
                'Content-Type'        => 'application/json',
                'Accept'              => 'application/json',
            ])->post($callbackUrl, $payload);

            if (!$response->successful()) {
                $sanitizedBody = substr(strip_tags($response->body()), 0, 200);
                \Illuminate\Support\Facades\Log::warning("Billing webhook failed for {$instance->name} (HTTP {$response->status()}): {$sanitizedBody}");
            }

            return $response;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Billing webhook exception for {$instance->name}: " . $e->getMessage());
            return null;
        }
    }
}
