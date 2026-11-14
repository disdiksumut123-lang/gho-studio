<?php

namespace App\Http\Controllers\Fas;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Services\AiClient;
use App\Services\Prompts;
use App\Support\Html;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function create(Request $r, Subject $subject)
    {
        $this->authorizeTraining($r->user(), $subject->agenda->training);
        return view('fas.activity-form', ['templates' => \App\Models\FormTemplate::orderBy('name')->get(), 'subject' => $subject, 'a' => new Activity(['type' => $r->get('type', 'tugas'), 'sequential' => true, 'required' => true, 'config' => []])]);
    }

    private function payload(Request $r): array
    {
        $d = $r->validate([
            'type' => 'required|in:'.implode(',', array_keys(Activity::TYPES)),
            'title' => 'required|max:200',
            'instructions' => 'nullable|string|max:200000',
            'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date',
            'cfg' => 'array',
        ]);
        $cfg = array_filter($r->input('cfg', []), fn ($v) => $v !== null && $v !== '');
        foreach (['rounds', 'passing', 'pages', 'seconds_per_page', 'min_chars'] as $n) if (isset($cfg[$n])) $cfg[$n] = (int) $cfg[$n];
        $cfg['hold_feedback'] = $r->boolean('cfg.hold_feedback');
        foreach (['form', 'output', 'page', 'fonts'] as $j) if (isset($cfg[$j]) && is_string($cfg[$j])) $cfg[$j] = json_decode($cfg[$j], true) ?: [];
        return [
            'type' => $d['type'], 'title' => $d['title'], 'instructions' => Html::clean($d['instructions'] ?? ''),
            'opens_at' => $d['opens_at'] ?? null, 'closes_at' => $d['closes_at'] ?? null, 'config' => $cfg,
            'sequential' => $r->boolean('sequential'), 'required' => $r->boolean('required'), 'allow_upload' => $r->boolean('allow_upload'),
            'owner_role' => $r->input('owner_role') === 'coach' ? 'coach' : 'fasilitator',
        ];
    }

    public function store(Request $r, Subject $subject)
    {
        $this->authorizeTraining($r->user(), $subject->agenda->training);
        $a = $subject->activities()->create($this->payload($r) + ['position' => (int) $subject->activities()->max('position') + 1]);
        return redirect()->route('fas.activity.edit', $a)->with('ok', $a->type === 'kuis' ? __('Aktivitas dibuat. Sekarang tambahkan soal.') : __('Aktivitas disimpan.'));
    }

    public function edit(Request $r, Activity $activity)
    {
        $this->authorizeTraining($r->user(), $activity->training());
        return view('fas.activity-form', ['templates' => \App\Models\FormTemplate::orderBy('name')->get(), 'subject' => $activity->subject, 'a' => $activity->load('questions')]);
    }

    public function update(Request $r, Activity $activity)
    {
        $this->authorizeTraining($r->user(), $activity->training());
        $activity->update($this->payload($r));
        return back()->with('ok', __('Aktivitas disimpan.'));
    }

    public function saveQuestions(Request $r, Activity $activity)
    {
        $this->authorizeTraining($r->user(), $activity->training());
        $items = json_decode($r->input('questions', '[]'), true) ?: [];
        $activity->questions()->delete();
        foreach (array_values($items) as $i => $q) $this->storeQuestion($activity, $q, $i + 1);
        return back()->with('ok', __(':n soal disimpan.', ['n' => count($items)]));
    }

    private function storeQuestion(Activity $a, array $q, int $pos): void
    {
        $type = array_key_exists($q['type'] ?? '', QuizQuestion::TYPES) ? $q['type'] : 'mcq';
        $data = match ($type) {
            'mcq', 'mcq_multi' => ['options' => array_values($q['options'] ?? [])],
            'tf_multi' => ['statements' => array_values($q['statements'] ?? [])],
            'matching' => ['left' => array_values($q['left'] ?? []), 'right' => array_values($q['right'] ?? [])],
            'ordering' => ['items' => array_values($q['items'] ?? [])],
            default => [],
        };
        $answer = $type === 'short' ? ['accepted' => array_values(array_filter($q['accepted'] ?? []))] : ['correct' => $q['correct'] ?? null];
        QuizQuestion::create(['activity_id' => $a->id, 'position' => $pos, 'type' => $type, 'prompt' => (string) ($q['prompt'] ?? ''), 'data' => $data, 'answer' => $answer, 'explanation' => $q['explanation'] ?? null]);
    }

    public function generateQuiz(Request $r, Activity $activity)
    {
        $this->authorizeTraining($r->user(), $activity->training());
        $d = $r->validate(['material' => 'required|string|min:40|max:60000', 'n' => 'required|integer|min:1|max:30', 'types' => 'required|array', 'level' => 'nullable|string']);
        try {
            [$sys, $user] = Prompts::quiz($d['material'], implode(', ', $d['types']), $d['n'], $d['level'] ?? 'campuran');
            $out = AiClient::default()->ask($sys, $user, true, 'buat_soal');
            return response()->json(['questions' => $out['questions'] ?? []]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function saveTemplate(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:160', 'config' => 'required|array']);
        $t = \App\Models\FormTemplate::create(['name' => $d['name'], 'config' => $d['config'], 'created_by' => $r->user()->id]);
        return response()->json(['id' => $t->id]);
    }

    public function design(Request $r)
    {
        $d = $r->validate(['type' => 'required|string', 'goal' => 'required|string|min:10|max:4000', 'training' => 'nullable|string']);
        try {
            [$sys, $user] = Prompts::design($d['type'], $d['goal'], $d['training'] ?? 'Pelatihan ASN');
            return response()->json(AiClient::default()->ask($sys, $user, true, 'perancang'));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
