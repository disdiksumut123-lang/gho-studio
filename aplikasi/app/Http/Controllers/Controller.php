<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\User;

abstract class Controller
{
    /** Pelatihan yang dikelola pengguna (sebagai fasilitator/coach), admin melihat semua. */
    protected function managedTrainings(User $u, ?string $role = null)
    {
        $q = Training::query();
        if (! in_array('admin', $u->roles ?? [], true)) {
            $q->where(fn ($w) => $w->where('created_by', $u->id)->orWhereHas('enrollments', fn ($e) => $e->where('user_id', $u->id)->whereIn('role', $role ? [$role] : ['fasilitator', 'coach'])));
        }
        return $q;
    }

    protected function authorizeTraining(User $u, Training $t): void
    {
        abort_unless($this->managedTrainings($u)->whereKey($t->id)->exists(), 403);
    }
}
