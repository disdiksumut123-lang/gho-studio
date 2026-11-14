<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@gho.test';
        $pass = env('ADMIN_PASSWORD') ?: 'ganti-sandi-ini';
        $admin = User::firstOrCreate(['email' => $email], ['name' => env('ADMIN_NAME') ?: 'Super Admin', 'password' => $pass, 'roles' => ['admin', 'coach', 'fasilitator', 'cms'], 'locale' => 'id']);

        if (! env('SEED_SAMPLE', true) || Training::exists()) return;

        $t = Training::create(['name' => 'Latsar CPNS Golongan III Angkatan XII (contoh)', 'type' => 'LATSAR', 'sequential' => true, 'created_by' => $admin->id, 'access_mode' => 'setujui']);
        Enrollment::create(['training_id' => $t->id, 'user_id' => $admin->id, 'role' => 'fasilitator', 'status' => 'aktif']);
        $ag = $t->agendas()->create(['name' => 'Agenda 2 · Nilai-Nilai Dasar PNS', 'position' => 1, 'sequential' => true]);
        $s = $ag->subjects()->create(['name' => 'Berorientasi Pelayanan', 'position' => 1, 'sequential' => true]);
        $mk = fn ($type, $title, $ins, $cfg = [], $extra = []) => $s->activities()->create(array_merge(['type' => $type, 'title' => $title, 'instructions' => $ins, 'config' => $cfg, 'position' => (int) $s->activities()->max('position') + 1, 'sequential' => true, 'required' => true, 'owner_role' => 'fasilitator'], $extra));
        $mk('materi', 'Pengantar Berorientasi Pelayanan', '<h2>Mengapa pelayanan?</h2><p>ASN hadir untuk melayani. Nilai <b>Berorientasi Pelayanan</b> berarti memahami dan memenuhi kebutuhan masyarakat, ramah, cekatan, solutif, dan terus memperbaiki diri.</p>');
        $mk('video', 'Video: BerAKHLAK dalam pelayanan', '<p>Tonton sampai selesai.</p>', ['url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ']);
        $mk('tugas', 'Hitung nilai ekonomi inovasimu', '<p>Jelaskan perhitungan penghematan waktu warga dari gagasan inovasi di unit kerjamu. Gunakan tombol <b>∑ Persamaan</b> untuk menulis rumus.</p>', ['rubric' => "Ketepatan rumus dan angka 40%\nAsumsi yang jelas 30%\nKaitan dengan dampak bagi warga 30%", 'min_chars' => 100], ['allow_upload' => true]);
        $mk('studi_kasus', 'Antrean pelayanan KTP-el', '<p>Kamu kepala seksi pelayanan di Disdukcapil. Antrean KTP-el mengular hingga 4 jam dan warga mulai mengeluh di media sosial.</p>', ['rounds' => 3]);
        $q = $mk('kuis', 'Kuis cek pemahaman', '<p>Jawab semua soal. Batas lulus 70.</p>', ['passing' => 70]);
        $qs = [
            ['mcq', 'Nilai dasar ASN yang menekankan pemenuhan kebutuhan masyarakat adalah…', ['options' => ['Akuntabel', 'Berorientasi Pelayanan', 'Harmonis', 'Loyal']], ['correct' => 1]],
            ['tf_multi', 'Tentukan benar atau salah.', ['statements' => ['Ramah termasuk perilaku berorientasi pelayanan', 'Pelayanan hanya tugas petugas loket', 'Inovasi bisa memperbaiki layanan']], ['correct' => [true, false, true]]],
            ['short', 'Singkatan nilai dasar ASN adalah ____.', [], ['accepted' => ['BerAKHLAK', 'berakhlak']]],
        ];
        foreach ($qs as $i => [$type, $prompt, $data, $ans]) QuizQuestion::create(['activity_id' => $q->id, 'position' => $i + 1, 'type' => $type, 'prompt' => $prompt, 'data' => $data, 'answer' => $ans]);
        $s2 = $ag->subjects()->create(['name' => 'Akuntabel', 'position' => 2, 'sequential' => true]);
        $s2->activities()->create(['type' => 'refleksi', 'title' => 'Learning journal: akuntabilitas', 'instructions' => '<p>Tulis pengalamanmu tentang akuntabilitas di tempat kerja.</p>', 'config' => [], 'position' => 1, 'sequential' => true, 'required' => true, 'owner_role' => 'coach']);
    }
}
