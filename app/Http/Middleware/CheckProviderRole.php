<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProviderRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated and has the 'provider' role
        if (Auth::check() && Auth::user()->hasRole('provider')) {
            return $next($request);
        }

        // If the user does not have the 'provider' role, return a 403 Forbidden response
        return response()->json(['message' => 'Forbidden'], 403);
    }
}
