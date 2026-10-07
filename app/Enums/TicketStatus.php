<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case Process = 'process';
    case Close = 'close';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu (Pending)',
            self::Process => 'Sedang Diproses',
            self::Close   => 'Selesai (Close)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400',
            self::Process => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-400',
            self::Close   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Close;
    }
}
