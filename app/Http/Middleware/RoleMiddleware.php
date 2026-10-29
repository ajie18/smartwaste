<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json([
                'error'   => 'Forbidden',
                'message' => ucfirst($role) . ' access required'
            ], 403);
        }

        return $next($request);
    }
}