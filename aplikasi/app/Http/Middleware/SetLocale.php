<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $loc = session('locale') ?? $request->user()?->locale ?? config('app.locale');
        app()->setLocale(in_array($loc, ['id', 'en'], true) ? $loc : 'id');
        return $next($request);
    }
}
