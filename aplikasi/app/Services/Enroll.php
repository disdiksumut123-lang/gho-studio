<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Invite;
use App\Models\Training;
use App\Models\User;

/**
 * Cara peserta masuk ke pelatihan:
 * - terbuka  : siapa pun dengan kode/tautan langsung aktif
 * - setujui  : bergabung, lalu menunggu persetujuan fasilitator (bawaan)
 * - undangan : hanya email yang diundang atau ada di daftar Excel
 * Email yang diundang selalu langsung aktif.
 */
class Enroll
{
    public const MODES = ['setujui' => 'Perlu persetujuan', 'undangan' => 'Hanya yang diundang', 'terbuka' => 'Terbuka dengan kode'];

    /** @return array{0:?Enrollment,1:?string} enrollment dan pesan */
    public static function join(User $u, Training $t, ?Cohort $c = null): array
    {
        $existing = Enrollment::where(['training_id' => $t->id, 'user_id' => $u->id, 'role' => 'peserta'])->first();
        if ($existing && $existing->status === 'aktif') return [$existing, null];
        $inv = self::inviteFor($u)->where('training_id', $t->id)->first();
        if ($inv) return [self::activate($u, $inv), null];
        if ($t->access_mode === 'undangan') return [null, __('Email Anda belum terdaftar sebagai peserta pelatihan ini. Hubungi fasilitator.')];
        $status = $t->access_mode === 'terbuka' ? 'aktif' : 'menunggu';
        $e = $existing ?? Enrollment::create(['training_id' => $t->id, 'user_id' => $u->id, 'role' => 'peserta', 'cohort_id' => $c?->id, 'status' => $status]);
        if ($existing && $existing->status === 'ditolak') return [$existing, __('Pendaftaran Anda ditolak. Hubungi fasilitator.')];
        self::addRole($u);
        return [$e, $e->status === 'menunggu' ? __('Pendaftaran diterima. Tunggu persetujuan fasilitator.') : null];
    }

    public static function activate(User $u, Invite $inv): Enrollment
    {
        $e = Enrollment::updateOrCreate(['training_id' => $inv->training_id, 'user_id' => $u->id, 'role' => 'peserta'], ['cohort_id' => $inv->cohort_id, 'status' => 'aktif']);
        $inv->update(['user_id' => $u->id]);
        $fill = array_filter(['nip' => $inv->nip, 'jabatan' => $inv->jabatan, 'unit_kerja' => $inv->unit_kerja, 'whatsapp' => $inv->whatsapp]);
        foreach ($fill as $k => $v) if (empty($u->$k)) $u->$k = $v;
        $u->save();
        self::addRole($u);
        return $e;
    }

    /** Dipanggil saat masuk/daftar: semua undangan untuk email ini langsung aktif. */
    public static function claimInvites(User $u): int
    {
        $n = 0;
        foreach (self::inviteFor($u)->get() as $inv) { self::activate($u, $inv); $n++; }
        return $n;
    }

    /** Undangan yang cocok dengan email atau nomor WhatsApp pengguna. */
    public static function inviteFor(User $u)
    {
        $wa = Invite::normalizeWa($u->whatsapp);
        return Invite::query()->where(fn ($q) => $q->whereRaw('lower(email) = ?', [mb_strtolower($u->email)])->when($wa, fn ($w) => $w->orWhere('whatsapp', $wa)));
    }

    private static function addRole(User $u): void
    {
        $roles = $u->roles ?? [];
        if (! in_array('peserta', $roles)) { $roles[] = 'peserta'; $u->update(['roles' => $roles]); }
    }
}
