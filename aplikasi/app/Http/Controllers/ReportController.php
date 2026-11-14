<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Training;
use App\Models\User;
use App\Services\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** Laporan diri sendiri (peserta). */
    public function mine(Request $r, Training $training)
    {
        $ok = Enrollment::where(['training_id' => $training->id, 'user_id' => $r->user()->id, 'status' => 'aktif'])->exists() || $this->managedTrainings($r->user())->whereKey($training->id)->exists();
        abort_unless($ok, 403);
        return view('report.person', ['t' => $training, 'u' => $r->user(), 'rep' => Report::build($r->user(), $training), 'staff' => false]);
    }

    /** Laporan satu peserta (fasilitator/coach). */
    public function person(Request $r, Training $training, User $user)
    {
        $this->authorizeTraining($r->user(), $training);
        return view('report.person', ['t' => $training, 'u' => $user, 'rep' => Report::build($user, $training), 'staff' => true]);
    }

    /** Rekap kelas: semua peserta per agenda. */
    public function training(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $training->load('agendas');
        $rows = Enrollment::with('user', 'cohort')->where(['training_id' => $training->id, 'role' => 'peserta', 'status' => 'aktif'])
            ->when($r->get('angkatan'), fn ($q, $c) => $q->where('cohort_id', $c))->get()
            ->map(fn ($e) => ['e' => $e, 'rep' => Report::build($e->user, $training)]);
        if ($r->get('unduh')) {
            $csv = "\xEF\xBB\xBF".implode(';', array_merge([__('Nama'), 'NIP', __('Angkatan')], $training->agendas->pluck('name')->all(), [__('Kemajuan'), __('Rata-rata nilai')]))."\n";
            foreach ($rows as $x) {
                $line = [$x['e']->user->name, $x['e']->user->nip, $x['e']->cohort?->name];
                foreach ($x['rep']['agendas'] as $a) $line[] = $a['pct'].'%';
                $line[] = $x['rep']['pct'].'%'; $line[] = $x['rep']['avg'];
                $csv .= implode(';', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $line))."\n";
            }
            return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="laporan-'.$training->id.'.csv"']);
        }
        return view('report.training', ['t' => $training->load('cohorts'), 'rows' => $rows]);
    }
}
