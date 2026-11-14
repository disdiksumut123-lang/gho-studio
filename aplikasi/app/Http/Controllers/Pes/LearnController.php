<?php

namespace App\Http\Controllers\Pes;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Answer;
use App\Models\CaseSession;
use App\Models\Completion;
use App\Models\Feedback;
use App\Models\MediaProgress;
use App\Models\QuizAttempt;
use App\Models\Training;
use App\Services\AiClient;
use App\Services\Progress;
use App\Services\Prompts;
use App\Support\Html;
use Illuminate\Http\Request;

class LearnController extends Controller
{
    private function trainings(Request $r)
    {
        $u = $r->user();
        $q = Training::query();
        if (! in_array('admin', $u->roles ?? [], true)) {
            $q->whereHas('enrollments', fn ($e) => $e->where('user_id', $u->id)->where('status', 'aktif'));
        }
        return $q;
    }

    private function guard(Request $r, Activity $a): array
    {
        $t = $a->training();
        abort_unless($this->trainings($r)->whereKey($t->id)->exists(), 403);
        $p = new Progress($r->user(), $t);
        $st = $p->status($a);
        $staff = $this->managedTrainings($r->user())->whereKey($t->id)->exists();
        if (! $st['ok'] && ! $staff) abort(redirect()->route('pes.training', $t)->with('err', Progress::reasonText($st['reason'])));
        return [$t, $p];
    }

    public function index(Request $r)
    {
        $list = $this->trainings($r)->latest()->get()->map(fn ($t) => ['t' => $t, 'pct' => (new Progress($r->user(), $t))->percent()]);
        $waiting = \App\Models\Enrollment::with('training')->where(['user_id' => $r->user()->id, 'role' => 'peserta'])->whereIn('status', ['menunggu', 'ditolak'])->get();
        return view('pes.home', ['list' => $list, 'waiting' => $waiting]);
    }

    public function join(Request $r)
    {
        $r->validate(['code' => 'required|string|max:20']);
        [$t, $msg] = AuthController::enrollByCode($r->user(), $r->input('code'));
        if (! $t) return back()->with('err', $msg);
        if ($msg) return back()->with('ok', $msg);
        return redirect()->route('pes.training', $t)->with('ok', __('Berhasil bergabung ke :n.', ['n' => $t->name]));
    }

    public function training(Request $r, Training $training)
    {
        abort_unless($this->trainings($r)->whereKey($training->id)->exists(), 403);
        $training->load('agendas.subjects.activities');
        $p = new Progress($r->user(), $training);
        $status = [];
        foreach ($training->agendas as $ag) foreach ($ag->subjects as $s) foreach ($s->activities as $a) $status[$a->id] = $p->isDone($a->id) ? ['ok' => true, 'reason' => 'selesai'] : $p->status($a);
        return view('pes.training', ['t' => $training, 'p' => $p, 'status' => $status, 'pct' => $p->percent()]);
    }

    public function activity(Request $r, Activity $activity)
    {
        [$t, $p] = $this->guard($r, $activity);
        $uid = $r->user()->id;
        $answer = Answer::where(['activity_id' => $activity->id, 'user_id' => $uid])->orderByDesc('version')->first();
        $submitted = Answer::where(['activity_id' => $activity->id, 'user_id' => $uid, 'submitted' => true])->orderByDesc('version')->with('feedback')->first();
        return view('pes.activity', [
            'a' => $activity, 't' => $t, 'done' => $p->isDone($activity->id),
            'answer' => $answer, 'submitted' => $submitted,
            'media' => MediaProgress::firstWhere(['activity_id' => $activity->id, 'user_id' => $uid]),
            'attempt' => QuizAttempt::where(['activity_id' => $activity->id, 'user_id' => $uid])->latest('id')->first(),
            'case' => CaseSession::where(['activity_id' => $activity->id, 'user_id' => $uid])->latest('id')->with('turns')->first(),
            'next' => $this->nextActivity($t, $activity),
        ]);
    }

    private function nextActivity(Training $t, Activity $a): ?Activity
    {
        $all = $t->loadMissing('agendas.subjects.activities')->agendas->flatMap(fn ($g) => $g->subjects)->flatMap(fn ($s) => $s->activities)->values();
        $i = $all->search(fn ($x) => $x->id === $a->id);
        return $i !== false ? $all->get($i + 1) : null;
    }

    /** Simpan otomatis. Setiap perubahan menjadi versi baru, versi lama tidak dihapus. */
    public function saveAnswer(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $body = Html::clean($r->input('body', ''));
        $last = Answer::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id])->orderByDesc('version')->first();
        if ($last && $last->body === $body) return response()->json(['ok' => true, 'version' => $last->version, 'same' => true]);
        $a = Answer::create(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'body' => $body, 'body_text' => $this->plain($body), 'version' => ($last->version ?? 0) + 1]);
        return response()->json(['ok' => true, 'version' => $a->version, 'at' => $a->created_at->format('H:i')]);
    }

    private function plain(string $html): string
    {
        $html = preg_replace('/<span[^>]*data-latex="([^"]*)"[^>]*>.*?<\/span>/s', ' [$1] ', $html);
        return trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>'], "\n", $html))));
    }

    public function submitAnswer(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $rules = ['body' => 'nullable|string|max:500000'];
        if ($activity->allow_upload) $rules['file'] = 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png';
        $r->validate($rules);
        $body = Html::clean($r->input('body', ''));
        $text = $this->plain($body);
        if (($max = (int) $activity->cfg('max_chars', 0)) && mb_strlen($text) > $max) return back()->with('err', __('Jawaban maksimal :n karakter.', ['n' => $max]));
        $min = (int) $activity->cfg('min_chars', 0);
        if (! $r->hasFile('file') && mb_strlen($text) < max(1, $min)) return back()->with('err', $min ? __('Jawaban minimal :n karakter.', ['n' => $min]) : __('Jawaban masih kosong.'));

        $last = Answer::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id])->max('version');
        $data = ['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'body' => $body, 'body_text' => $text, 'version' => (int) $last + 1, 'submitted' => true];
        if ($r->hasFile('file')) {
            $f = $r->file('file');
            $data['file_path'] = $f->store('jawaban/'.$activity->id, 'local');
            $data['file_name'] = $f->getClientOriginalName();
        }
        $answer = Answer::create($data); // 1) simpan dulu
        Completion::mark($activity->id, $r->user()->id);

        return $this->grade($answer) // 2) baru minta tanggapan
            ? redirect()->route('pes.activity', $activity)->with('ok', __('Jawaban terkirim dan tersimpan.'))
            : redirect()->route('pes.activity', $activity)->with('err', __('Jawaban tersimpan. Tanggapan belum bisa dibuat, tekan "Minta tanggapan" untuk mencoba lagi.'));
    }

    private function grade(Answer $answer): bool
    {
        $a = $answer->activity;
        $text = $answer->body_text ?: ($answer->file_name ? __('(Peserta mengunggah berkas: :f)', ['f' => $answer->file_name]) : '');
        try {
            [$sys, $user] = Prompts::grade($a, $text);
            $out = AiClient::default()->ask($sys, $user, true, 'tanggapan_tugas');
            Feedback::create(['answer_id' => $answer->id, 'content' => $out['feedback'] ?? '', 'scores' => $out['scores'] ?? [], 'total' => isset($out['total']) ? (int) $out['total'] : null, 'status' => $a->cfg('hold_feedback') ? 'menunggu' : 'auto']);
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function feedback(Request $r, Answer $answer)
    {
        abort_unless($answer->user_id === $r->user()->id, 403);
        return back()->with(...($this->grade($answer) ? ['ok', __('Tanggapan sudah dibuat.')] : ['err', __('Masih belum bisa. Coba beberapa saat lagi.')]));
    }

    public function download(Request $r, Answer $answer)
    {
        $ok = $answer->user_id === $r->user()->id || $this->managedTrainings($r->user())->whereKey($answer->activity->training()->id)->exists();
        abort_unless($ok && $answer->file_path, 404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($answer->file_path, $answer->file_name);
    }

    public function complete(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        abort_unless($activity->type === 'materi', 422);
        Completion::mark($activity->id, $r->user()->id);
        return back()->with('ok', __('Ditandai selesai.'));
    }

    /** Berkas video/audio/sampul yang diunggah langsung (bukan tautan luar), tersimpan di disk privat. */
    public function mediaFile(Request $r, Activity $activity, string $field)
    {
        $this->guard($r, $activity);
        abort_unless(in_array($field, ['url', 'cover'], true), 404);
        $path = (string) $activity->cfg($field);
        abort_unless(str_starts_with($path, 'materi/') && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);
        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($path));
    }

    /**
     * Kemajuan video/audio/ebook. Video dan audio tidak bisa dilompati:
     * posisi hanya dihitung jika tidak melompat lebih dari 15 detik dari yang sudah ditonton.
     */
    public function mediaProgress(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $m = MediaProgress::firstOrCreate(['activity_id' => $activity->id, 'user_id' => $r->user()->id]);
        $done = false;
        if (in_array($activity->type, ['video', 'audio'])) {
            $pos = (int) $r->input('position', 0);
            $dur = (int) $r->input('duration', 0);
            if ($dur > 0) $m->duration = $dur;
            if ($pos > $m->max_position && $pos <= $m->max_position + 15) $m->max_position = $pos;
            if ($r->boolean('ended') && $m->duration && $m->max_position >= $m->duration - 15) $m->max_position = $m->duration;
            $done = $m->duration > 0 && $m->max_position >= $m->duration * 0.97;
        } elseif ($activity->type === 'ebook') {
            $m->active_seconds = min($m->active_seconds + min((int) $r->input('tick', 0), 30), 86400);
            $pages = $m->pages_seen ?? [];
            if ($pg = (int) $r->input('page')) $pages[] = $pg;
            $m->pages_seen = array_values(array_unique($pages));
            $need = (int) $activity->cfg('pages', 0) * (int) $activity->cfg('seconds_per_page', 20);
            $done = $m->active_seconds >= $need && $r->boolean('confirm_last');
        }
        $m->save();
        if ($done) Completion::mark($activity->id, $r->user()->id);
        return response()->json(['max' => $m->max_position, 'seconds' => $m->active_seconds, 'done' => $done || Completion::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id])->exists()]);
    }

    public function submitQuiz(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $given = $r->input('q', []);
        $qs = $activity->questions;
        if ($qs->isEmpty()) return back()->with('err', __('Kuis belum memiliki soal.'));
        $right = 0; $res = [];
        foreach ($qs as $q) {
            $ok = $q->check($given[$q->id] ?? null);
            $right += $ok ? 1 : 0;
            $res[$q->id] = ['given' => $given[$q->id] ?? null, 'ok' => $ok];
        }
        $score = (int) round($right / $qs->count() * 100);
        QuizAttempt::create(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'answers' => $res, 'score' => $score, 'finished_at' => now()]);
        $pass = (int) $activity->cfg('passing', 70);
        if ($score >= $pass) {
            Completion::mark($activity->id, $r->user()->id);
            return back()->with('ok', __('Nilai :s. Lulus, aktivitas berikutnya terbuka.', ['s' => $score]));
        }
        return back()->with('err', __('Nilai :s. Batas lulus :p, silakan coba lagi.', ['s' => $score, 'p' => $pass]));
    }
}
