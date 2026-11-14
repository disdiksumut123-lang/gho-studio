<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $u = $request->user();
        $ok = $role === 'peserta' ? in_array('peserta', $u->switchableRoles(), true) : $u->hasRole($role);
        if (! $ok) abort(403, __('Anda tidak punya akses ke halaman ini.'));
        session(['role' => $role === 'admin' ? session('role', 'fasilitator') : $role]);
        return $next($request);
    }
}
