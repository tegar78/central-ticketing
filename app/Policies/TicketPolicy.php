<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->role === UserRole::Technician->value) {
            return $ticket->assigned_technician_id === $user->id;
        }

        return in_array($user->role, [UserRole::Admin->value, UserRole::Operator->value], true);
    }

    /**
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::Technician->value;
    }

    /**
     * Determine whether the user can assign a technician to the ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        return in_array($user->role, [UserRole::Admin->value, UserRole::Operator->value], true);
    }

    /**
     * Determine whether the user can update the status of the ticket.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        if ($user->role === UserRole::Technician->value) {
            return $ticket->assigned_technician_id === $user->id;
        }

        return in_array($user->role, [UserRole::Admin->value, UserRole::Operator->value], true);
    }
}
