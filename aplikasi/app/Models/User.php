<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'coach', 'fasilitator', 'cms', 'peserta'];

    protected $fillable = ['name', 'email', 'password', 'roles', 'locale', 'nip', 'jabatan', 'unit_kerja', 'whatsapp'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'roles' => 'array'];
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->roles ?? [];
        return in_array($role, $roles, true) || ($role !== 'peserta' && in_array('admin', $roles, true));
    }

    /** Peran yang bisa dipilih di pengalih peran. */
    public function switchableRoles(): array
    {
        $out = [];
        foreach (['coach', 'fasilitator', 'cms', 'peserta'] as $r) {
            if ($this->hasRole($r) || ($r === 'peserta' && $this->enrollments()->where('role', 'peserta')->exists())) {
                $out[] = $r;
            }
        }
        if (in_array('admin', $this->roles ?? [], true) && ! in_array('peserta', $out)) {
            $out[] = 'peserta'; // admin boleh pratinjau sebagai peserta
        }
        return $out ?: ['peserta'];
    }

    public function enrollments() { return $this->hasMany(Enrollment::class); }
}
