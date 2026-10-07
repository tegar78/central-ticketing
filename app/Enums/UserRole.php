<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case Technician = 'technician';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Operator => 'Operator Call Center',
            self::Technician => 'Teknisi Lapangan',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isOperator(): bool
    {
        return $this === self::Operator;
    }

    public function isTechnician(): bool
    {
        return $this === self::Technician;
    }
}
