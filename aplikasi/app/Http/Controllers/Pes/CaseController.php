<?php

namespace App\Http\Controllers\Pes;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CaseSession;
use App\Models\CaseTurn;
use App\Models\Completion;
use App\Services\AiClient;
use App\Services\Progress;
use App\Services\Prompts;
use Illuminate\Http\Request;

/**
 * Studi kasus interaktif: satu alur. Setiap jawaban peserta DISIMPAN DULU,
 * baru kemudian tanggapan dan perubahan kondisi dibuat.
 */
class CaseController extends Controller
{
    private function own(Request $r, CaseSession $s): void
    {
        abort_unless($s->user_id === $r->user()->id, 403);
    }

    private function json(CaseSession $s)
    {
        $s->load('turns');
        return response()->json([
            'id' => $s->id, 'round' => $s->round, 'max' => (int) $s->activity->cfg('rounds', 3), 'status' => $s->status, 'final' => $s->final,
            'turns' => $s->turns->map(fn ($t) => ['role' => $t->role, 'round' => $t->round, 'content' => $t->content, 'meta' => $t->meta]),
            'pending' => $this->pending($s) !== null,
        ]);
    }

    /** Jawaban peserta terakhir yang belum ditanggapi. */
    private function pending(CaseSession $s): ?CaseTurn
    {
        $last = $s->turns()->reorder()->orderByDesc('id')->first();
        return $last && $last->role === 'participant' ? $last : null;
    }

    public function start(Request $r, Activity $activity)
    {
        $st = (new Progress($r->user(), $activity->training()))->status($activity);
        abort_unless($st['ok'] || $this->managedTrainings($r->user())->whereKey($activity->training()->id)->exists(), 403);
        $s = CaseSession::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'status' => 'berjalan'])->latest('id')->first()
            ?? CaseSession::create(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'status' => 'berjalan', 'round' => 0]);
        if (! $s->turns()->where('role', 'opening')->exists()) {
            $fixed = trim((string) $activity->cfg('opening'));
            if ($fixed !== '') {
                CaseTurn::create(['case_session_id' => $s->id, 'round' => 0, 'role' => 'opening', 'content' => $fixed, 'meta' => ['question' => $activity->cfg('opening_question')]]);
            } else {
                try {
                    [$sys, $user] = Prompts::caseOpen($activity);
                    $o = AiClient::default()->ask($sys, $user, true, 'kasus_pembuka');
                    CaseTurn::create(['case_session_id' => $s->id, 'round' => 0, 'role' => 'opening', 'content' => (string) ($o['situation'] ?? ''), 'meta' => ['title' => $o['title'] ?? null, 'question' => $o['question'] ?? null]]);
                } catch (\Throwable $e) {
                    return response()->json(['error' => $e->getMessage()], 503);
                }
            }
        }
        return $this->json($s);
    }

    public function answer(Request $r, CaseSession $session)
    {
        $this->own($r, $session);
        $r->validate(['content' => 'required|string|min:5|max:20000']);
        abort_if($session->status !== 'berjalan', 422);
        if (! $this->pending($session)) {
            $session->increment('round');
            CaseTurn::create(['case_session_id' => $session->id, 'round' => $session->round, 'role' => 'participant', 'content' => $r->input('content')]);
        }
        return $this->json($session->fresh());
    }

    public function respond(Request $r, CaseSession $session)
    {
        $this->own($r, $session);
        $p = $this->pending($session);
        if (! $p) return $this->json($session);
        $a = $session->activity;
        $max = (int) $a->cfg('rounds', 3);
        $hist = $session->turns->map(fn ($t) => ['role' => $t->role, 'content' => $t->content.($t->meta['question'] ?? '' ? "\nPertanyaan: ".$t->meta['question'] : '')])->all();
        try {
            [$sys, $user] = Prompts::caseTurn($a, $hist, $p->content, $session->round, $max);
            $o = AiClient::default()->ask($sys, $user, true, 'kasus_putaran');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'saved' => true], 503);
        }
        CaseTurn::create(['case_session_id' => $session->id, 'round' => $session->round, 'role' => 'feedback', 'content' => (string) ($o['feedback'] ?? '')]);
        if ($session->round >= $max || empty($o['condition'])) {
            $final = $o['final'] ?? ['scores' => [], 'total' => null];
            CaseTurn::create(['case_session_id' => $session->id, 'round' => $session->round, 'role' => 'final', 'content' => (string) ($final['development'] ?? ''), 'meta' => $final]);
            $session->update(['status' => 'selesai', 'final' => $final]);
            Completion::mark($a->id, $session->user_id);
        } else {
            $c = $o['condition'];
            CaseTurn::create(['case_session_id' => $session->id, 'round' => $session->round, 'role' => 'condition', 'content' => (string) ($c['text'] ?? ''), 'meta' => ['category' => $c['category'] ?? null, 'impact' => $c['impact'] ?? null, 'question' => $c['question'] ?? null]]);
        }
        return $this->json($session->fresh());
    }
}
