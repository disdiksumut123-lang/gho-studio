<?php

namespace App\Http\Controllers;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Training;
use App\Models\Invite;
use App\Models\User;
use App\Services\Enroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function login(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($cred, $r->boolean('remember'))) {
            return back()->withErrors(['email' => __('Email atau kata sandi salah.')])->onlyInput('email');
        }
        $r->session()->regenerate();
        Enroll::claimInvites($r->user());
        $this->useInviteFromSession($r);
        return redirect()->intended(route('home'));
    }

    public function register(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'code' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:30',
        ]);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => $d['password'], 'roles' => ['peserta'], 'locale' => app()->getLocale(), 'whatsapp' => Invite::normalizeWa($d['whatsapp'] ?? null), 'nip' => $d['nip'] ?? null]);
        Auth::login($user);
        Enroll::claimInvites($user);
        $this->useInviteFromSession($r);
        if (! empty($d['code'])) { [, $msg] = self::enrollByCode($user, $d['code']); if ($msg) session()->flash('ok', $msg); }
        return redirect()->intended(route('pes.home'));
    }

    /** @return array{0:?Training,1:?string} */
    public static function enrollByCode(User $user, string $code): array
    {
        $code = strtoupper(trim($code));
        $cohort = Cohort::where('join_code', $code)->first();
        $training = $cohort?->training ?? Training::where('join_code', $code)->first();
        if (! $training) return [null, __('Kode tidak ditemukan. Periksa lagi ke fasilitator.')];
        [$e, $msg] = Enroll::join($user, $training, $cohort);
        return [$e ? $training : null, $msg];
    }

    private function useInviteFromSession(Request $r): void
    {
        if ($tok = session()->pull('invite_token')) {
            $inv = Invite::where('token', $tok)->first();
            if ($inv) { Enroll::activate($r->user(), $inv); session(['url.intended' => route('pes.training', $inv->training_id)]); }
        }
    }

    /** Tautan undangan pribadi (dikirim lewat WhatsApp atau email). */
    public function invite(Request $r, string $token)
    {
        $inv = Invite::where('token', $token)->firstOrFail();
        if ($r->user()) { Enroll::activate($r->user(), $inv); session(['role' => 'peserta']); return redirect()->route('pes.training', $inv->training_id); }
        session(['invite_token' => $token, 'invite_prefill' => ['name' => $inv->name, 'email' => $inv->email, 'whatsapp' => $inv->whatsapp, 'nip' => $inv->nip]]);
        return redirect()->route($inv->email && User::where('email', $inv->email)->exists() ? 'login' : 'register')->with('ok', __('Undangan untuk :t. Lengkapi data untuk masuk kelas.', ['t' => $inv->training->name]));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/login');
    }

    public function home(Request $r)
    {
        $roles = $r->user()->switchableRoles();
        $role = session('role');
        if (! in_array($role, $roles, true)) $role = $roles[0];
        return redirect(self::roleHome($role));
    }

    public static function roleHome(string $role): string
    {
        return match ($role) { 'coach' => route('coach.home'), 'fasilitator' => route('fas.home'), 'cms' => route('cms.home'), default => route('pes.home') };
    }

    public function switchRole(Request $r, string $role)
    {
        abort_unless(in_array($role, $r->user()->switchableRoles(), true), 403);
        session(['role' => $role]);
        return redirect(self::roleHome($role));
    }

    public function locale(Request $r, string $loc)
    {
        $loc = in_array($loc, ['id', 'en'], true) ? $loc : 'id';
        session(['locale' => $loc]);
        $r->user()?->update(['locale' => $loc]);
        return back();
    }
}
