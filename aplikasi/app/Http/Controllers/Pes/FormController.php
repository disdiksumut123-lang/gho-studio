<?php

namespace App\Http\Controllers\Pes;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Answer;
use App\Models\Completion;
use App\Models\Document;
use App\Models\User;
use App\Services\AiClient;
use App\Services\Progress;
use App\Services\Prompts;
use App\Support\Html;
use Illuminate\Http\Request;

/** Formulir dinamis: peserta mengisi, lalu dokumen disusun per bagian output. */
class FormController extends Controller
{
    private function guard(Request $r, Activity $a): void
    {
        abort_unless($a->type === 'formulir', 404);
        $staff = $this->managedTrainings($r->user())->whereKey($a->training()->id)->exists();
        if ($staff) return;
        $st = (new Progress($r->user(), $a->training()))->status($a);
        abort_unless($st['ok'] || Completion::where(['activity_id' => $a->id, 'user_id' => $r->user()->id])->exists(), 403);
    }

    public static function fields(Activity $a): array
    {
        return collect($a->cfg('form.sections', []))->flatMap(fn ($s) => $s['fields'] ?? [])->keyBy('key')->all();
    }

    public static function values(Activity $a, int $userId): array
    {
        $ans = Answer::where(['activity_id' => $a->id, 'user_id' => $userId, 'item_key' => 'form'])->orderByDesc('version')->first();
        return $ans ? (json_decode((string) $ans->body, true) ?: []) : [];
    }

    /** Bersihkan nilai sesuai jenis isian. */
    private function clean(Activity $a, array $in): array
    {
        $out = []; $max = (int) $a->cfg('max_chars', 0);
        foreach (self::fields($a) as $k => $f) {
            if (! array_key_exists($k, $in)) continue;
            $v = $in[$k];
            $v = match ($f['type'] ?? 'text') {
                'textarea' => Html::clean((string) $v),
                'checkbox' => array_values(array_map('strval', (array) $v)),
                'table' => array_values(array_map(fn ($row) => array_map(fn ($c) => mb_substr(strip_tags((string) $c), 0, 2000), (array) $row), (array) $v)),
                'file' => $v,
                default => mb_substr(strip_tags((string) $v), 0, 5000),
            };
            if ($max && is_string($v) && mb_strlen(strip_tags($v)) > $max) $v = mb_substr($v, 0, $max * 2);
            $out[$k] = $v;
        }
        return $out;
    }

    private function plain(Activity $a, array $vals): string
    {
        $lines = [];
        foreach (self::fields($a) as $k => $f) {
            $v = $vals[$k] ?? null; if ($v === null || $v === '' || $v === []) continue;
            $lines[] = ($f['label'] ?? $k).': '.self::text($f, $v);
        }
        return implode("\n", $lines);
    }

    public static function text(array $f, $v): string
    {
        return match ($f['type'] ?? 'text') {
            'textarea' => trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>'], "\n", (string) $v)))),
            'checkbox' => implode(', ', (array) $v),
            'table' => implode("\n", array_map(fn ($row) => '| '.implode(' | ', (array) $row).' |', (array) $v)),
            'file' => is_array($v) ? ($v['name'] ?? '') : (string) $v,
            default => (string) $v,
        };
    }

    public function save(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $vals = array_merge(self::values($activity, $r->user()->id), $this->clean($activity, (array) $r->input('values', [])));
        $body = json_encode($vals, JSON_UNESCAPED_UNICODE);
        $last = Answer::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'item_key' => 'form'])->orderByDesc('version')->first();
        if ($last && $last->body === $body) return response()->json(['ok' => true]);
        $a = Answer::create(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'item_key' => 'form', 'body' => $body, 'body_text' => $this->plain($activity, $vals), 'version' => ($last->version ?? 0) + 1]);
        return response()->json(['ok' => true, 'at' => $a->created_at->format('H:i')]);
    }

    public function submit(Request $r, Activity $activity)
    {
        $this->guard($r, $activity);
        $vals = array_merge(self::values($activity, $r->user()->id), $this->clean($activity, (array) json_decode((string) $r->input('values', '{}'), true)));
        foreach (self::fields($activity) as $k => $f) {
            if (($f['type'] ?? '') === 'file' && $r->hasFile('file_'.$k)) {
                $r->validate(['file_'.$k => 'file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png']);
                $file = $r->file('file_'.$k);
                $vals[$k] = ['path' => $file->store('formulir/'.$activity->id, 'local'), 'name' => $file->getClientOriginalName()];
            }
        }
        $missing = collect(self::fields($activity))->filter(fn ($f, $k) => ! empty($f['required']) && (($vals[$k] ?? '') === '' || ($vals[$k] ?? null) === [] || trim(strip_tags(is_string($vals[$k] ?? '') ? $vals[$k] : 'x')) === ''))->pluck('label');
        if ($missing->isNotEmpty()) return back()->with('err', __('Isian wajib belum lengkap: :f', ['f' => $missing->take(5)->implode(', ')]));
        $last = Answer::where(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'item_key' => 'form'])->max('version');
        $ans = Answer::create(['activity_id' => $activity->id, 'user_id' => $r->user()->id, 'item_key' => 'form', 'body' => json_encode($vals, JSON_UNESCAPED_UNICODE), 'body_text' => $this->plain($activity, $vals), 'version' => (int) $last + 1, 'submitted' => true]);
        $doc = Document::firstOrCreate(['activity_id' => $activity->id, 'user_id' => $r->user()->id], ['status' => 'draf']);
        $doc->update(['answer_id' => $ans->id]);
        if (! $activity->cfg('hold_feedback')) Completion::mark($activity->id, $r->user()->id);
        return redirect()->route('pes.doc', [$activity, $r->user()])->with('ok', __('Isian terkirim. Tekan "Susun" pada tiap bagian untuk menyusun dokumen.'));
    }

    private function docGuard(Request $r, Activity $a, User $u): bool
    {
        $staff = $this->managedTrainings($r->user())->whereKey($a->training()->id)->exists();
        abort_unless($staff || $u->id === $r->user()->id, 403);
        return $staff;
    }

    public function document(Request $r, Activity $activity, User $user)
    {
        $staff = $this->docGuard($r, $activity, $user);
        $doc = Document::firstOrCreate(['activity_id' => $activity->id, 'user_id' => $user->id], ['status' => 'draf']);
        return view('pes.document', ['a' => $activity, 'u' => $user, 'doc' => $doc, 'vals' => self::values($activity, $user->id), 'staff' => $staff]);
    }

    public function compose(Request $r, Activity $activity, User $user)
    {
        $this->docGuard($r, $activity, $user);
        $key = $r->input('key');
        $sec = collect($activity->cfg('output.sections', []))->firstWhere('key', $key) ?? abort(404);
        $vals = self::values($activity, $user->id);
        $fields = self::fields($activity);
        $prompt = preg_replace_callback('/\{([a-z0-9_]+)\}/', fn ($m) => isset($fields[$m[1]]) ? self::text($fields[$m[1]], $vals[$m[1]] ?? '') : $m[0], (string) ($sec['prompt'] ?? ''));
        try {
            [$sys, $usr] = Prompts::composeSection($activity, $sec['title'] ?? '', $prompt, $this->plain($activity, $vals));
            $out = AiClient::default()->ask($sys, $usr, true, 'susun_dokumen');
            $html = Html::clean((string) ($out['html'] ?? ''));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        $doc = Document::firstOrCreate(['activity_id' => $activity->id, 'user_id' => $user->id], ['status' => 'draf']);
        $doc->update(['sections' => array_merge($doc->sections ?? [], [$key => $html]), 'updated_by' => $r->user()->id]);
        return response()->json(['html' => $html]);
    }

    public function update(Request $r, Activity $activity, User $user)
    {
        $staff = $this->docGuard($r, $activity, $user);
        $doc = Document::firstOrCreate(['activity_id' => $activity->id, 'user_id' => $user->id], ['status' => 'draf']);
        $secs = $doc->sections ?? [];
        foreach ((array) $r->input('sections', []) as $k => $html) $secs[$k] = Html::clean((string) $html);
        $data = ['sections' => $secs, 'updated_by' => $r->user()->id];
        if ($staff && in_array($r->input('status'), ['disetujui', 'dikembalikan'], true)) {
            $data['status'] = $r->input('status'); $data['reviewer_note'] = $r->input('note');
            $r->input('status') === 'disetujui' ? Completion::mark($activity->id, $user->id) : Completion::where(['activity_id' => $activity->id, 'user_id' => $user->id])->delete();
        }
        $doc->update($data);
        return $r->expectsJson() ? response()->json(['ok' => true]) : back()->with('ok', __('Dokumen disimpan.'));
    }
}
