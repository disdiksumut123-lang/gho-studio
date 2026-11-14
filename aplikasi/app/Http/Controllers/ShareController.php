<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Agenda;
use App\Models\ShareLink;
use App\Models\Subject;
use App\Models\Training;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    private function resolve(string $level, int $id): array
    {
        return match ($level) {
            'pelatihan' => [$t = Training::findOrFail($id), $t, $t->name],
            'agenda' => [$x = Agenda::findOrFail($id), $x->training, $x->name],
            'mapel' => [$x = Subject::findOrFail($id), $x->agenda->training, $x->name],
            'aktivitas' => [$x = Activity::findOrFail($id), $x->training(), $x->title],
            default => abort(404),
        };
    }

    public function store(Request $r, string $level, int $id)
    {
        [, $t, $name] = $this->resolve($level, $id);
        $this->authorizeTraining($r->user(), $t);
        $d = $r->validate(['opens_at' => 'nullable|date', 'closes_at' => 'nullable|date|after:opens_at']);
        $l = ShareLink::create(['training_id' => $t->id, 'level' => $level, 'item_id' => $id, 'label' => $name, 'opens_at' => $d['opens_at'] ?? null, 'closes_at' => $d['closes_at'] ?? null, 'created_by' => $r->user()->id]);
        return back()->with('ok', __('Tautan dibuat. Salin dan bagikan ke peserta.'))->with('share', $l->url());
    }

    public function destroy(Request $r, ShareLink $link)
    {
        $this->authorizeTraining($r->user(), $link->training);
        $link->delete();
        return back()->with('ok', __('Tautan dihapus.'));
    }

    /** Dibuka peserta. Tamu diarahkan masuk/daftar dulu, lalu kembali ke sini. */
    public function open(Request $r, string $token)
    {
        $l = ShareLink::where('token', $token)->firstOrFail();
        if ($st = $l->state()) {
            return response()->view('share-closed', ['l' => $l, 'st' => $st], 403);
        }
        if (! $r->user()) {
            session(['url.intended' => $r->fullUrl(), 'share_code' => $l->training->join_code]);
            return redirect()->route('login')->with('ok', __('Masuk atau daftar dulu untuk membuka :n.', ['n' => $l->label]));
        }
        $u = $r->user();
        $staff = $this->managedTrainings($u)->whereKey($l->training_id)->exists();
        if (! $staff) {
            [$e, $msg] = \App\Services\Enroll::join($u, $l->training);
            if (! $e || $e->status !== 'aktif') return redirect()->route('pes.home')->with($e ? 'ok' : 'err', $msg);
        }
        $l->increment('opened');
        session(['role' => 'peserta', 'share_until.'.$l->training_id => $l->closes_at?->timestamp]);
        return match ($l->level) {
            'aktivitas' => redirect()->route('pes.activity', $l->item_id),
            'pelatihan' => redirect()->route('pes.training', $l->training_id),
            default => redirect()->to(route('pes.training', $l->training_id).'#'.($l->level === 'agenda' ? 'ag' : 'mp').$l->item_id),
        };
    }
}
