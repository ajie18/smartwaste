<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $hasSpatieRole = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);
        $hasLegacyRole = $user && in_array($user->role, $roles, true);

        if (!$user || (!$hasSpatieRole && !$hasLegacyRole)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
