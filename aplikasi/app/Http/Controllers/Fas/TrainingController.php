<?php

namespace App\Http\Controllers\Fas;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Agenda;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    private const LEVELS = ['pelatihan' => Training::class, 'agenda' => Agenda::class, 'mapel' => Subject::class, 'aktivitas' => Activity::class];

    public function index(Request $r)
    {
        $trainings = $this->managedTrainings($r->user())->withCount(['enrollments as peserta_count' => fn ($q) => $q->where('role', 'peserta')])->latest()->get();
        return view('fas.home', compact('trainings'));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:160', 'type' => 'required|in:'.implode(',', array_keys(Training::TYPES)), 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date|after_or_equal:opens_at']);
        $d['use_cohorts'] = $r->boolean('use_cohorts');
        $d['created_by'] = $r->user()->id;
        $t = Training::create($d);
        Enrollment::firstOrCreate(['training_id' => $t->id, 'user_id' => $r->user()->id, 'role' => 'fasilitator']);
        return redirect()->route('fas.training.show', $t)->with('ok', __('Pelatihan dibuat. Tambahkan agenda pertama.'));
    }

    public function show(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $training->load('agendas.subjects.activities', 'cohorts');
        return view('fas.structure', ['t' => $training]);
    }

    public function update(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $d = $r->validate(['name' => 'required|max:160', 'type' => 'required', 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date|after_or_equal:opens_at']);
        $d['use_cohorts'] = $r->boolean('use_cohorts');
        $d['access_mode'] = array_key_exists($r->input('access_mode'), \App\Services\Enroll::MODES) ? $r->input('access_mode') : 'setujui';
        $training->update($d);
        return back()->with('ok', __('Pelatihan diperbarui.'));
    }

    public function destroy(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $training->delete();
        return redirect()->route('fas.home')->with('ok', __('Pelatihan dihapus.'));
    }

    public function participants(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $training->load('cohorts');
        $enr = Enrollment::with('user', 'cohort')->where('training_id', $training->id)->orderByRaw("case status when 'menunggu' then 0 else 1 end")->orderBy('role')->get();
        $invites = \App\Models\Invite::with('cohort')->where('training_id', $training->id)->whereNull('user_id')->latest()->get();
        return view('fas.participants', ['t' => $training, 'enrollments' => $enr, 'invites' => $invites, 'gateway' => \App\Services\WhatsApp::enabled()]);
    }

    public function addCohort(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $r->validate(['name' => 'required|max:80']);
        Cohort::create(['training_id' => $training->id, 'name' => $r->name, 'join_code' => Training::newCode($training->type)]);
        $training->update(['use_cohorts' => true]);
        return back()->with('ok', __('Angkatan ditambahkan.'));
    }

    public function addStaff(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $d = $r->validate(['email' => 'required|email|exists:users,email', 'role' => 'required|in:coach,fasilitator,peserta', 'cohort_id' => 'nullable|exists:cohorts,id']);
        $u = User::where('email', $d['email'])->first();
        Enrollment::updateOrCreate(['training_id' => $training->id, 'user_id' => $u->id, 'role' => $d['role']], ['cohort_id' => $d['cohort_id'] ?? null, 'status' => 'aktif']);
        $roles = $u->roles ?? []; if (! in_array($d['role'], $roles)) { $roles[] = $d['role']; $u->update(['roles' => $roles]); }
        return back()->with('ok', __(':n ditambahkan sebagai :r.', ['n' => $u->name, 'r' => __(ucfirst($d['role']))]));
    }

    public function addAgenda(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $d = $r->validate(['name' => 'required|max:160', 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date']);
        $d['position'] = (int) $training->agendas()->max('position') + 1;
        $training->agendas()->create($d);
        return back()->with('ok', __('Agenda ditambahkan.'));
    }

    public function addSubject(Request $r, Agenda $agenda)
    {
        $this->authorizeTraining($r->user(), $agenda->training);
        $d = $r->validate(['name' => 'required|max:160', 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date']);
        $d['position'] = (int) $agenda->subjects()->max('position') + 1;
        $agenda->subjects()->create($d);
        return back()->with('ok', __('Mata pelatihan ditambahkan.'));
    }

    private function find(string $level, int $id)
    {
        $cls = self::LEVELS[$level] ?? abort(404);
        return $cls::findOrFail($id);
    }

    private function trainingOf($item): Training
    {
        return match (true) {
            $item instanceof Training => $item,
            $item instanceof Agenda => $item->training,
            $item instanceof Subject => $item->agenda->training,
            $item instanceof Activity => $item->training(),
        };
    }

    public function updateItem(Request $r, string $level, int $id)
    {
        $item = $this->find($level, $id);
        $this->authorizeTraining($r->user(), $this->trainingOf($item));
        $d = $r->validate(['name' => 'sometimes|required|max:160', 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date']);
        if ($item instanceof Activity && isset($d['name'])) { $d['title'] = $d['name']; unset($d['name']); }
        // Jadwal tingkat bawah tidak boleh melewati induk
        $parent = match (true) { $item instanceof Agenda => $item->training, $item instanceof Subject => $item->agenda, $item instanceof Activity => $item->subject, default => null };
        $warn = null;
        if ($parent && ! empty($d['closes_at']) && $parent->closes_at && now()->parse($d['closes_at'])->gt($parent->closes_at)) $warn = __('Jadwal tutup melewati jadwal induknya. Periksa lagi.');
        $item->update($d);
        return back()->with('ok', __('Disimpan.'))->with('err', $warn);
    }

    public function toggleSequential(Request $r, string $level, int $id)
    {
        $item = $this->find($level, $id);
        $this->authorizeTraining($r->user(), $this->trainingOf($item));
        $item->update(['sequential' => ! $item->sequential]);
        if ($r->expectsJson()) return response()->json(['sequential' => $item->sequential]);
        return back();
    }

    public function move(Request $r, string $level, int $id, string $dir)
    {
        $item = $this->find($level, $id);
        abort_if($item instanceof Training, 404);
        $this->authorizeTraining($r->user(), $this->trainingOf($item));
        $fk = match (true) { $item instanceof Agenda => 'training_id', $item instanceof Subject => 'agenda_id', default => 'subject_id' };
        $sibs = $item::where($fk, $item->$fk)->orderBy('position')->orderBy('id')->get()->values();
        $i = $sibs->search(fn ($x) => $x->id === $item->id);
        $j = $dir === 'naik' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < $sibs->count()) {
            $arr = $sibs->all(); [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
            foreach ($arr as $k => $x) $x->update(['position' => $k + 1]);
        }
        return back();
    }

    public function deleteItem(Request $r, string $level, int $id)
    {
        $item = $this->find($level, $id);
        abort_if($item instanceof Training, 404);
        $this->authorizeTraining($r->user(), $this->trainingOf($item));
        $item->delete();
        return back()->with('ok', __('Dihapus.'));
    }
}
