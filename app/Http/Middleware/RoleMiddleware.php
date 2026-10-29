<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! $request->user() || $request->user()->role !== $role) {
            if ($request->is('admin/*') && ! $request->expectsJson()) {
                abort(403, ucfirst($role).' access required.');
            }

            return response()->json([
                'error' => 'Forbidden',
                'message' => ucfirst($role).' access required',
            ], 403);
        }

        return $next($request);
    }
}
