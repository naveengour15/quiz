<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetAppLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale');

        if (! $locale) {
            $locale = Auth::check() ? (Auth::user()->language ?? 'en') : 'en';
            if (! in_array($locale, ['en', 'hi', 'hinglish'], true)) {
                $locale = 'en';
            }
            session(['locale' => $locale]);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
