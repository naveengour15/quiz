<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PanelMiddleware
{
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        switch ($panel) {

            case 'superadmin':

                if (!Auth::guard('superadmin')->check()) {
                    return redirect()->route('superadmin.login');
                }

                break;

            case 'admin':

                if (!Auth::guard('admin')->check()) {
                    return redirect()->route('superadmin.login');
                }

                break;

            case 'user':

                if (!Auth::guard('user')->check()) {
                    return redirect()->route('superadmin.login');
                }

                break;

            default:
                abort(403, 'Invalid Panel');
        }

        return $next($request);
    }
}