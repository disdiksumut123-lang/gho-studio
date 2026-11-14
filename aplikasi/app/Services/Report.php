<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\CaseSession;
use App\Models\Completion;
use App\Models\QuizAttempt;
use App\Models\Training;
use App\Models\User;

/** Laporan per Aktivitas, Mata Pelatihan, dan Agenda untuk satu peserta. */
class Report
{
    public static function build(User $u, Training $t): array
    {
        $t->loadMissing('agendas.subjects.activities');
        $p = new Progress($u, $t);
        $ids = $t->agendas->flatMap(fn ($a) => $a->subjects)->flatMap(fn ($s) => $s->activities)->pluck('id');
        $done = Completion::where('user_id', $u->id)->whereIn('activity_id', $ids)->pluck('completed_at', 'activity_id');
        $quiz = QuizAttempt::where('user_id', $u->id)->whereIn('activity_id', $ids)->selectRaw('activity_id, max(score) s, count(*) n')->groupBy('activity_id')->get()->keyBy('activity_id');
        $ans = Answer::with('feedback')->where(['user_id' => $u->id, 'submitted' => true])->whereIn('activity_id', $ids)->orderByDesc('version')->get()->unique('activity_id')->keyBy('activity_id');
        $case = CaseSession::where('user_id', $u->id)->whereIn('activity_id', $ids)->where('status', 'selesai')->get()->keyBy('activity_id');
        $current = null; $out = ['agendas' => [], 'done' => 0, 'total' => 0, 'scores' => []];
        foreach ($t->agendas as $ag) {
            $A = ['m' => $ag, 'subjects' => [], 'done' => 0, 'total' => 0, 'scores' => []];
            foreach ($ag->subjects as $s) {
                $S = ['m' => $s, 'acts' => [], 'done' => 0, 'total' => 0, 'scores' => []];
                foreach ($s->activities as $a) {
                    $score = null; $note = null;
                    if ($a->type === 'kuis' && isset($quiz[$a->id])) { $score = (int) $quiz[$a->id]->s; $note = __(':n kali mencoba', ['n' => $quiz[$a->id]->n]); }
                    elseif (isset($ans[$a->id])) { $fb = $ans[$a->id]->feedback; if ($fb && in_array($fb->status, ['auto', 'approved'])) $score = $fb->total; $note = $fb?->status === 'returned' ? __('Dikembalikan untuk diperbaiki') : ($fb?->status === 'menunggu' ? __('Menunggu tinjauan') : null); }
                    elseif (isset($case[$a->id])) { $score = $case[$a->id]->final['total'] ?? null; }
                    $isDone = isset($done[$a->id]);
                    $st = $isDone ? ['ok' => true, 'reason' => null] : $p->status($a);
                    $state = $isDone ? 'selesai' : ($st['ok'] ? 'terbuka' : 'terkunci');
                    if (! $isDone && $st['ok'] && ! $current) $current = $a->id;
                    $S['acts'][] = ['m' => $a, 'state' => $state, 'reason' => $st['reason'], 'score' => $score, 'note' => $note, 'at' => $done[$a->id] ?? null, 'current' => false];
                    if ($a->required) { $S['total']++; if ($isDone) $S['done']++; }
                    if ($score !== null) $S['scores'][] = $score;
                }
                foreach ($S['acts'] as &$x) $x['current'] = $x['m']->id === $current; unset($x);
                $S['pct'] = $S['total'] ? (int) round($S['done'] / $S['total'] * 100) : 100;
                $S['avg'] = $S['scores'] ? (int) round(array_sum($S['scores']) / count($S['scores'])) : null;
                $A['done'] += $S['done']; $A['total'] += $S['total']; $A['scores'] = array_merge($A['scores'], $S['scores']);
                $A['subjects'][] = $S;
            }
            $A['pct'] = $A['total'] ? (int) round($A['done'] / $A['total'] * 100) : 100;
            $A['avg'] = $A['scores'] ? (int) round(array_sum($A['scores']) / count($A['scores'])) : null;
            $out['done'] += $A['done']; $out['total'] += $A['total']; $out['scores'] = array_merge($out['scores'], $A['scores']);
            $out['agendas'][] = $A;
        }
        $out['pct'] = $out['total'] ? (int) round($out['done'] / $out['total'] * 100) : 0;
        $out['avg'] = $out['scores'] ? (int) round(array_sum($out['scores']) / count($out['scores'])) : null;
        $out['current'] = $current;
        return $out;
    }
}
