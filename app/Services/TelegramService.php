<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $this->chatId   = config('services.telegram.chat_id') ?: env('TELEGRAM_GROUP_CHAT_ID');
    }

    /**
     * Check if Telegram credentials are fully set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    /**
     * Send a raw HTML text message to Telegram group / chat.
     */
    public function sendMessage(string $text, ?string $targetChatId = null): bool
    {
        $chatId = $targetChatId ?: $this->chatId;

        if (empty($this->botToken) || empty($chatId)) {
            Log::info("TelegramService: Bot token or chat ID is empty. Skipping notification.");
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

            $response = Http::withoutVerifying()
                ->timeout(5)
                ->asJson()
                ->post($url, [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => false,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning("TelegramService: Failed to send message (HTTP {$response->status()}): " . $response->body());
            return false;
        } catch (Throwable $e) {
            Log::warning("TelegramService: Exception while sending message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build and send formatted notification for ticket lifecycle events.
     *
     * @param Ticket $ticket
     * @param string $eventType 'ticket_created' | 'technician_assigned' | 'status_updated'
     * @param string|null $remark
     * @param User|null $actor
     * @return bool
     */
    public function sendTicketNotification(Ticket $ticket, string $eventType, ?string $remark = null, ?User $actor = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        // Header and Icon based on event & status
        $status = strtolower($ticket->status);
        switch ($eventType) {
            case 'ticket_created':
                $headerTitle = "🆕 <b>TIKET GANGGUAN BARU</b>";
                break;
            case 'technician_assigned':
                $headerTitle = "👷 <b>PENUGASAN TEKNISI TIKET</b>";
                break;
            case 'status_updated':
            default:
                if ($status === 'close' || $status === 'done') {
                    $headerTitle = "✅ <b>TIKET SELESAI (CLOSED)</b>";
                } elseif ($status === 'process') {
                    $headerTitle = "⚙️ <b>TIKET SEDANG DIPROSES</b>";
                } else {
                    $headerTitle = "🛠️ <b>UPDATE STATUS TIKET</b>";
                }
                break;
        }

        // Badge Status
        $badgeStatus = match ($status) {
            'process'        => '🔵 <b>SEDANG DIPROSES</b>',
            'close', 'done'  => '🟢 <b>SELESAI (CLOSE)</b>',
            default          => '🟡 <b>PENDING (MENUNGGU)</b>',
        };

        // Sanitasi teks aman HTML
        $escNumber   = htmlspecialchars($ticket->ticket_number, ENT_QUOTES, 'UTF-8');
        $escTenant   = htmlspecialchars($ticket->billingInstance?->name ?? 'Central System', ENT_QUOTES, 'UTF-8');
        $escName     = htmlspecialchars($ticket->customer_name, ENT_QUOTES, 'UTF-8');
        $escServices = htmlspecialchars($ticket->no_services ?? '-', ENT_QUOTES, 'UTF-8');
        $escPhone    = htmlspecialchars($ticket->customer_phone ?? '-', ENT_QUOTES, 'UTF-8');
        $escAddress  = htmlspecialchars($ticket->customer_address ?? '-', ENT_QUOTES, 'UTF-8');
        $escCategory = htmlspecialchars($ticket->category_name ?? 'Gangguan Umum', ENT_QUOTES, 'UTF-8');
        $escProblem  = htmlspecialchars($ticket->problem_description ?? '-', ENT_QUOTES, 'UTF-8');

        // Teknisi
        $techName = $ticket->assignedTechnician?->name;
        $escTech  = $techName ? htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') : '<i>Belum ditugaskan</i>';

        // Aktor / Pengupdate
        $actorName = $actor?->name ?? $ticket->created_by_name ?? 'System';
        $actorRole = $actor?->role ? ucfirst($actor->role) : ($ticket->created_by_role ? ucfirst($ticket->created_by_role) : 'Admin');
        $escActor  = htmlspecialchars("{$actorName} ({$actorRole})", ENT_QUOTES, 'UTF-8');

        $timeNow = now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') . ' WIB';

        // Susun template pesan
        $lines = [
            $headerTitle,
            "━━━━━━━━━━━━━━━━━━━━",
            "🏢 <b>Instansi:</b> {$escTenant}",
            "📌 <b>No Tiket:</b> <code>#{$escNumber}</code>",
            "👤 <b>Pelanggan:</b> {$escName} ({$escServices})",
            "📞 <b>No Telp:</b> {$escPhone}",
            "📍 <b>Alamat:</b> {$escAddress}",
            "⚠️ <b>Topik Gangguan:</b> {$escCategory}",
            "📋 <b>Keluhan:</b> {$escProblem}",
            "",
            "📊 <b>Status:</b> {$badgeStatus}",
            "👷 <b>Teknisi:</b> {$escTech}",
        ];

        if (!empty($remark)) {
            $escRemark = htmlspecialchars($remark, ENT_QUOTES, 'UTF-8');
            $lines[] = "📝 <b>Catatan / Tindakan:</b>\n<i>{$escRemark}</i>";
        }

        $lines[] = "";
        $lines[] = "👤 <b>Oleh:</b> {$escActor}";
        $lines[] = "🕒 <b>Waktu:</b> {$timeNow}";

        // Tautan peta jika koordinat tersedia
        if (!empty($ticket->latitude) && !empty($ticket->longitude)) {
            $lat = trim($ticket->latitude);
            $lng = trim($ticket->longitude);
            $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
            $lines[] = "━━━━━━━━━━━━━━━━━━━━";
            $lines[] = "🗺️ <a href=\"{$mapsUrl}\">Buka Rute Google Maps Pelanggan</a>";
        }

        $message = implode("\n", $lines);

        return $this->sendMessage($message);
    }
}
