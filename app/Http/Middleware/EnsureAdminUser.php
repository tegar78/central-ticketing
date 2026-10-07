<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || !$user->is_active || $user->role !== UserRole::Admin->value) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ditolak. Hanya Administrator yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
