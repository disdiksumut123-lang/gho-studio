<?php

namespace App\Http\Controllers\Fas;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Invite;
use App\Models\Training;
use App\Models\User;
use App\Services\Enroll;
use App\Services\WhatsApp;
use App\Support\Sheet;
use Illuminate\Http\Request;

/** Undangan peserta: satu per satu, unggah Excel/CSV, kirim lewat WhatsApp, dan persetujuan pendaftar. */
class InviteController extends Controller
{
    private function add(Training $t, array $row, ?int $cohortId): ?Invite
    {
        $email = filter_var(trim($row['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
        $wa = Invite::normalizeWa($row['whatsapp'] ?? null);
        if (! $email && ! $wa) return null;
        $q = Invite::where('training_id', $t->id)->where(fn ($w) => $w->when($email, fn ($x) => $x->orWhereRaw('lower(email) = ?', [mb_strtolower($email)]))->when($wa, fn ($x) => $x->orWhere('whatsapp', $wa)));
        $inv = $q->first() ?? new Invite(['training_id' => $t->id]);
        $inv->fill(array_filter(['name' => trim($row['name'] ?? ''), 'nip' => preg_replace('/\D/', '', $row['nip'] ?? ''), 'email' => $email, 'whatsapp' => $wa,
            'jabatan' => trim($row['jabatan'] ?? ''), 'unit_kerja' => trim($row['unit_kerja'] ?? ''), 'cohort_id' => $cohortId]))->save();
        // Pengguna yang sudah punya akun langsung diaktifkan
        $u = $email ? User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first() : null;
        $u ??= $wa ? User::where('whatsapp', $wa)->first() : null;
        if ($u) Enroll::activate($u, $inv);
        return $inv;
    }

    public function store(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $d = $r->validate(['name' => 'nullable|max:160', 'nip' => 'nullable|max:30', 'email' => 'nullable|email|required_without:whatsapp', 'whatsapp' => 'nullable|max:20|required_without:email', 'cohort_id' => 'nullable|exists:cohorts,id']);
        $inv = $this->add($training, $d, $d['cohort_id'] ?? null);
        if (! $inv) return back()->with('err', __('Isi email atau nomor WhatsApp yang benar.'));
        return back()->with('ok', $inv->user_id ? __(':n sudah punya akun dan langsung aktif.', ['n' => $inv->name ?: $inv->email]) : __('Undangan dibuat. Kirim lewat tombol WhatsApp atau salin tautannya.'));
    }

    public function import(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $r->validate(['file' => 'required|file|max:5120', 'cohort_id' => 'nullable|exists:cohorts,id']);
        $f = $r->file('file'); $ext = strtolower($f->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'csv'])) return back()->with('err', __('Gunakan file .xlsx atau .csv.'));
        $rows = Sheet::read($f->getRealPath(), $ext);
        if (count($rows) < 2) return back()->with('err', __('File kosong atau tidak terbaca.'));
        $map = [];
        $alias = ['name' => ['nama', 'nama lengkap', 'name'], 'nip' => ['nip'], 'email' => ['email', 'e-mail', 'surel'], 'whatsapp' => ['whatsapp', 'wa', 'no wa', 'nomor wa', 'no. wa', 'hp', 'no hp', 'nomor hp', 'telepon'], 'jabatan' => ['jabatan'], 'unit_kerja' => ['unit kerja', 'instansi', 'unit_kerja', 'opd']];
        foreach ($rows[0] as $i => $h) { $h = mb_strtolower(trim($h)); foreach ($alias as $k => $names) if (in_array($h, $names, true)) $map[$k] = $i; }
        if (! isset($map['email']) && ! isset($map['whatsapp'])) return back()->with('err', __('Baris pertama harus berisi judul kolom, minimal Email atau WhatsApp.'));
        $ok = 0; $skip = 0;
        foreach (array_slice($rows, 1) as $row) {
            $data = []; foreach ($map as $k => $i) $data[$k] = $row[$i] ?? '';
            $this->add($training, $data, $r->input('cohort_id')) ? $ok++ : $skip++;
        }
        return back()->with('ok', __(':n calon peserta dimasukkan.', ['n' => $ok]).($skip ? ' '.__(':n baris dilewati karena tanpa email/WA.', ['n' => $skip]) : ''));
    }

    public function template(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $csv = "Nama;NIP;Email;WhatsApp;Jabatan;Unit Kerja\nBudi Santoso, S.Kom;199001012020121001;budi@contoh.go.id;081234567890;Pranata Komputer;Dinas Kominfo\n";
        return response("\xEF\xBB\xBF".$csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="templat-peserta.csv"']);
    }

    public function send(Request $r, Invite $invite)
    {
        $this->authorizeTraining($r->user(), $invite->training);
        try {
            WhatsApp::send($invite->whatsapp ?? throw new \RuntimeException(__('Nomor WhatsApp kosong.')), $invite->message());
            $invite->update(['sent_at' => now()]);
            return back()->with('ok', __('Undangan terkirim ke :w.', ['w' => $invite->whatsapp]));
        } catch (\Throwable $e) {
            return back()->with('err', __('Belum terkirim:').' '.$e->getMessage());
        }
    }

    public function sendAll(Request $r, Training $training)
    {
        $this->authorizeTraining($r->user(), $training);
        $ok = 0; $fail = 0;
        foreach (Invite::where('training_id', $training->id)->whereNull('user_id')->whereNull('sent_at')->whereNotNull('whatsapp')->limit(50)->get() as $inv) {
            try { WhatsApp::send($inv->whatsapp, $inv->message()); $inv->update(['sent_at' => now()]); $ok++; usleep(800000); }
            catch (\Throwable $e) { $fail++; }
        }
        return back()->with($fail ? 'err' : 'ok', __(':n undangan terkirim.', ['n' => $ok]).($fail ? ' '.__(':n gagal.', ['n' => $fail]) : ''));
    }

    public function destroy(Request $r, Invite $invite)
    {
        $this->authorizeTraining($r->user(), $invite->training);
        $invite->delete();
        return back()->with('ok', __('Undangan dihapus.'));
    }

    public function decide(Request $r, Enrollment $enrollment, string $action)
    {
        $this->authorizeTraining($r->user(), $enrollment->training);
        abort_unless(in_array($action, ['setujui', 'tolak', 'semua']), 404);
        if ($action === 'semua') {
            $n = Enrollment::where(['training_id' => $enrollment->training_id, 'status' => 'menunggu'])->update(['status' => 'aktif']);
            return back()->with('ok', __(':n pendaftar disetujui.', ['n' => $n]));
        }
        $enrollment->update(['status' => $action === 'setujui' ? 'aktif' : 'ditolak']);
        return back()->with('ok', $action === 'setujui' ? __(':n disetujui.', ['n' => $enrollment->user->name]) : __(':n ditolak.', ['n' => $enrollment->user->name]));
    }
}
