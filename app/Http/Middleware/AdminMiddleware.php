<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $adminRoles = [
            'admin_full_access', 'admin_laman', 'admin_member',
            'admin_bnh', 'organizer', 'bendahara',
        ];

        if (!in_array(auth()->user()->role, $adminRoles)) {
            abort(403, 'Hanya untuk admin.');
        }

        return $next($request);
    }
}
