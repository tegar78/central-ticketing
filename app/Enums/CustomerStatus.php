<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerStatus: string
{
    case Active   = 'active';
    case Isolated = 'isolated';
    case Inactive = 'inactive';
    case Free     = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Aktif',
            self::Isolated => 'Terisolir',
            self::Inactive => 'Nonaktif',
            self::Free     => 'Gratis/Trial',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400',
            self::Isolated => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400',
            self::Inactive => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-400',
            self::Free     => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-400',
        };
    }
}
