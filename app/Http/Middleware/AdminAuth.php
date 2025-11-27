<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     * Redirect to login if not authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_logged_in')) {
            // For API requests, return JSON error
            if ($request->expectsJson() || $request->is('api/*') || $request->is('*/api/*')) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // For web requests, redirect to login
            return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
        }

        return $next($request);
    }
}

