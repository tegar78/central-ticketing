<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\BillingInstance;
use App\Models\User;
use App\Models\Customer;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketWebController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Display centralized tickets directory with search, filters & export options
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Ticket::with(['billingInstance', 'assignedTechnician']);

        // Role scoping: technician only sees assigned tickets
        if ($user->role === 'technician') {
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
        if ($user->role === 'technician') {
            $baseCountQuery->where('assigned_technician_id', $user->id);
        }

        $pendingCount = (clone $baseCountQuery)->where('status', 'pending')->count();
        $processCount = (clone $baseCountQuery)->where('status', 'process')->count();
        $closeCount = (clone $baseCountQuery)->where('status', 'close')->count();
        $totalCount = (clone $baseCountQuery)->count();

        $tenants = BillingInstance::where('is_active', true)->get();
        $technicians = User::where('role', 'technician')->where('is_active', true)->get();
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

        $deviceInfo = $this->getClientDeviceInfo($request);

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'remark' => "Tambah Tiket Gangguan dari {$deviceInfo}",
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

        // Kirim Notifikasi ke Grup Telegram
        $this->telegramService->sendTicketNotification($ticket, 'ticket_created', $ticket->problem_description, $user);

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

        if ($ticket->isClosed()) {
            return back()->with('error', 'Tiket ini telah berstatus Selesai (Closed) dan terkunci. Penugasan teknisi tidak dapat diubah lagi.');
        }

        $technician = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_technician_id' => $technician->id,
        ]);

        $deviceInfo = $this->getClientDeviceInfo($request);
        $remark = "Ditugaskan ke teknisi: {$technician->name} dari {$deviceInfo}";

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

        // Kirim Notifikasi ke Grup Telegram
        $this->telegramService->sendTicketNotification($ticket, 'technician_assigned', $remark, $user);

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

        if ($ticket->isClosed()) {
            return back()->with('error', 'Tiket ini telah berstatus Selesai (Closed) dan terkunci. Status tidak dapat diperbarui lagi.');
        }

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

        $deviceInfo = $this->getClientDeviceInfo($request);
        $statusLabels = ['pending' => 'Pending', 'process' => 'Dalam Proses', 'close' => 'Selesai (Close)'];
        $statusLabel = $statusLabels[$validated['status']] ?? $validated['status'];
        $remarkText = "Ubah status ke {$statusLabel}: {$validated['remark']} dari {$deviceInfo}";

        TicketTimeline::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'status' => $validated['status'],
            'remark' => $remarkText,
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

        // Kirim Notifikasi ke Grup Telegram
        $this->telegramService->sendTicketNotification($ticket, 'status_updated', $validated['remark'], $user);

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
}
