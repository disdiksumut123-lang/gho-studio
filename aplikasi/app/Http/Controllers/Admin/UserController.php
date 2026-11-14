<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $r)
    {
        $q = User::query()->orderBy('name');
        if ($s = $r->get('q')) $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        return view('admin.users', ['users' => $q->paginate(30)->withQueryString()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:120', 'email' => 'required|email|unique:users', 'password' => 'required|min:8', 'roles' => 'array']);
        User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => $d['password'], 'roles' => array_values(array_intersect($d['roles'] ?? ['peserta'], User::ROLES))]);
        return back()->with('ok', __('Pengguna ditambahkan.'));
    }

    public function update(Request $r, User $user)
    {
        $roles = array_values(array_intersect($r->input('roles', []), User::ROLES));
        if ($user->id === $r->user()->id && ! in_array('admin', $roles)) $roles[] = 'admin'; // jangan kunci diri sendiri
        $data = ['roles' => $roles];
        if ($r->filled('password')) { $r->validate(['password' => 'min:8']); $data['password'] = $r->password; }
        $user->update($data);
        return back()->with('ok', __('Peran :n diperbarui.', ['n' => $user->name]));
    }
}
