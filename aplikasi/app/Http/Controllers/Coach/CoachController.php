<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Feedback;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    /** Jenis bimbingan coach (sesuai pembagian peran). */
    public const COACH_KINDS = [
        'LATSAR' => ['Rancangan Aktualisasi'],
        'PKP' => ['Aksi Perubahan', 'Learning journal', 'Lesson learned studi lapangan'],
        'PKA' => ['Aksi Perubahan', 'Learning journal', 'Lesson learned studi lapangan'],
        'PKN2' => ['Rancangan Proyek Perubahan', 'Learning journal', 'Lesson learned'],
    ];

    public function index(Request $r)
    {
        $trainings = $this->managedTrainings($r->user(), 'coach')->withCount('participants')->latest()->get();
        $ids = $trainings->pluck('id');
        $acts = Activity::where('owner_role', 'coach')->whereHas('subject.agenda', fn ($q) => $q->whereIn('training_id', $ids))->with('subject.agenda.training')->get();
        $pending = Feedback::where('status', 'menunggu')->whereHas('answer', fn ($q) => $q->whereIn('activity_id', $acts->pluck('id')))->count();
        return view('coach.home', compact('trainings', 'acts', 'pending'));
    }

    /** Antrean jawaban untuk ditinjau. Coach melihat aktivitas coach, fasilitator melihat aktivitas fasilitator. */
    public function review(Request $r)
    {
        $role = $r->routeIs('coach.*') ? 'coach' : 'fasilitator';
        $ids = $this->managedTrainings($r->user(), $r->user()->hasRole('admin') ? null : $role)->pluck('id');
        $status = $r->get('status', 'menunggu');
        $q = Feedback::with(['answer.user', 'answer.activity.subject.agenda.training'])
            ->whereHas('answer.activity', fn ($a) => $a->where('owner_role', $role)->whereHas('subject.agenda', fn ($g) => $g->whereIn('training_id', $ids)));
        if ($status !== 'semua') $q->where('status', $status);
        $docSt = ['semua' => ['draf', 'ditinjau', 'disetujui', 'dikembalikan'], 'approved' => ['disetujui'], 'returned' => ['dikembalikan']][$status] ?? ['draf', 'ditinjau'];
        $docs = \App\Models\Document::with(['user', 'activity'])->whereNotNull('answer_id')->whereIn('status', $docSt)
            ->whereHas('activity', fn ($a) => $a->where('owner_role', $role)->whereHas('subject.agenda', fn ($g) => $g->whereIn('training_id', $ids)))->latest('updated_at')->limit(50)->get();
        return view('coach.review', ['items' => $q->latest()->paginate(20)->withQueryString(), 'status' => $status, 'role' => $role, 'docs' => $docs]);
    }

    private function authorizeFeedback(Request $r, Feedback $f): void
    {
        $this->authorizeTraining($r->user(), $f->answer->activity->training());
    }

    public function approve(Request $r, Feedback $feedback)
    {
        $this->authorizeFeedback($r, $feedback);
        $d = $r->validate(['content' => 'nullable|string|max:20000', 'note' => 'nullable|string|max:5000']);
        $feedback->update(['status' => 'approved', 'content' => $d['content'] ?? $feedback->content, 'reviewer_note' => $d['note'] ?? null, 'reviewed_by' => $r->user()->id]);
        return back()->with('ok', __('Tanggapan disetujui dan sudah terlihat oleh peserta.'));
    }

    public function returnBack(Request $r, Feedback $feedback)
    {
        $this->authorizeFeedback($r, $feedback);
        $d = $r->validate(['note' => 'required|string|max:5000']);
        $feedback->update(['status' => 'returned', 'reviewer_note' => $d['note'], 'reviewed_by' => $r->user()->id]);
        Completion::where(['activity_id' => $feedback->answer->activity_id, 'user_id' => $feedback->answer->user_id])->delete();
        return back()->with('ok', __('Dikembalikan ke peserta untuk diperbaiki.'));
    }
}
