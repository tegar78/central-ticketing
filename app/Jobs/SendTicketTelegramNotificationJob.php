<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketTelegramNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?string $remark,
        public ?User $actor = null
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $telegramService->sendTicketNotification(
            $this->ticket,
            $this->event,
            $this->remark ?? '',
            $this->actor
        );
    }
}
