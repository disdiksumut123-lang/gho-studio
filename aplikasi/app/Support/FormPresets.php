<?php

namespace App\Support;

/** Templat formulir siap pakai untuk rancangan coach. Fasilitator/coach bebas mengubahnya. */
class FormPresets
{
    private static function f(string $key, string $label, string $type = 'textarea', bool $req = true, array $extra = []): array
    {
        return array_merge(['key' => $key, 'label' => $label, 'type' => $type, 'required' => $req, 'help' => '', 'options' => [], 'columns' => []], $extra);
    }

    private static function s(string $key, string $title, array $fields, string $help = ''): array
    {
        return ['key' => $key, 'title' => $title, 'help' => $help, 'fields' => $fields];
    }

    private static function o(string $key, string $title, string $prompt): array
    {
        return compact('key', 'title', 'prompt');
    }

    private static function identity(): array
    {
        return self::s('identitas', 'Identitas', [
            self::f('nama', 'Nama lengkap', 'text'), self::f('nip', 'NIP', 'text', false), self::f('jabatan', 'Jabatan', 'text'),
            self::f('unit', 'Unit kerja / instansi', 'text'), self::f('mentor', 'Nama mentor / atasan langsung', 'text', false), self::f('coach', 'Nama coach', 'text', false),
        ]);
    }

    private static function wrap(array $sections, array $outputs, array $page = []): array
    {
        return ['form' => ['sections' => $sections], 'output' => ['sections' => $outputs], 'page' => array_merge(Page::defaults(), $page), 'fonts' => Page::fontDefaults()];
    }

    public static function all(): array
    {
        $plan = self::f('kegiatan', 'Rencana kegiatan', 'table', true, ['columns' => ['Kegiatan', 'Tahapan', 'Output', 'Waktu']]);
        return [
            ['name' => 'Rancangan Aktualisasi (Latsar)', 'training_type' => 'latsar', 'config' => self::wrap([
                self::identity(),
                self::s('isu', 'Identifikasi isu', [
                    self::f('daftar_isu', 'Daftar isu di unit kerja', 'table', true, ['columns' => ['Isu', 'Sumber isu', 'Kondisi saat ini']]),
                    self::f('analisis', 'Alat analisis isu', 'select', true, ['options' => ['APKL', 'USG', 'Fishbone', 'Lainnya']]),
                    self::f('isu_terpilih', 'Isu terpilih dan alasannya'),
                    self::f('gagasan', 'Gagasan kreatif pemecahan isu'),
                ]),
                self::s('nilai', 'Kaitan nilai dasar', [
                    self::f('berakhlak', 'Nilai BerAKHLAK yang diaktualisasikan', 'checkbox', true, ['options' => ['Berorientasi Pelayanan', 'Akuntabel', 'Kompeten', 'Harmonis', 'Loyal', 'Adaptif', 'Kolaboratif']]),
                    self::f('smart_asn', 'Kaitan dengan Smart ASN dan manajemen ASN'),
                    self::f('visi_misi', 'Kaitan dengan visi, misi, dan nilai organisasi'),
                ]),
                self::s('rencana', 'Rencana kegiatan', [$plan, self::f('jadwal_mulai', 'Tanggal mulai habituasi', 'date', false)]),
            ], [
                self::o('pendahuluan', 'BAB I Pendahuluan', 'Susun latar belakang dari isu {isu_terpilih} di {unit}, tujuan, dan manfaat aktualisasi.'),
                self::o('isu', 'BAB II Identifikasi dan penetapan isu', 'Uraikan daftar isu {daftar_isu}, analisis dengan {analisis}, dan alasan penetapan {isu_terpilih}. Sajikan tabel bila perlu.'),
                self::o('gagasan', 'BAB III Gagasan pemecahan isu', 'Jelaskan gagasan {gagasan} dan kaitannya dengan nilai {berakhlak}, {smart_asn}, serta {visi_misi}.'),
                self::o('rencana', 'BAB IV Rencana kegiatan aktualisasi', 'Buat tabel rencana dari {kegiatan}: kegiatan, tahapan, output, kaitan nilai BerAKHLAK, dan waktu.'),
            ])],
            ['name' => 'Aksi Perubahan (PKP/PKA)', 'training_type' => 'pkp', 'config' => self::wrap([
                self::identity(),
                self::s('perubahan', 'Gagasan perubahan', [
                    self::f('judul', 'Judul aksi perubahan', 'text'), self::f('latar', 'Latar belakang dan kondisi saat ini'),
                    self::f('area', 'Area perubahan', 'select', true, ['options' => ['Pelayanan publik', 'Tata kelola internal', 'Sumber daya manusia', 'Teknologi informasi', 'Lainnya']]),
                    self::f('tujuan', 'Tujuan jangka pendek, menengah, dan panjang'), self::f('manfaat', 'Manfaat bagi organisasi dan pemangku kepentingan'),
                ]),
                self::s('tata', 'Tata kelola', [
                    self::f('tim', 'Tim efektif', 'table', true, ['columns' => ['Nama', 'Peran', 'Tugas']]),
                    self::f('stakeholder', 'Pemangku kepentingan', 'table', true, ['columns' => ['Pemangku kepentingan', 'Kepentingan', 'Pengaruh', 'Strategi']]),
                    $plan, self::f('risiko', 'Risiko dan cara mengatasinya', 'textarea', false),
                ]),
            ], [
                self::o('pendahuluan', 'BAB I Pendahuluan', 'Susun latar belakang {latar}, area {area}, judul {judul}, tujuan {tujuan}, dan manfaat {manfaat}.'),
                self::o('tata', 'BAB II Tata kelola aksi perubahan', 'Uraikan tim efektif {tim} dan peta pemangku kepentingan {stakeholder} beserta strategi komunikasinya.'),
                self::o('milestone', 'BAB III Milestone dan rencana kegiatan', 'Buat tabel milestone jangka pendek dari {kegiatan}.'),
                self::o('risiko', 'BAB IV Manajemen risiko', 'Uraikan risiko dan mitigasi dari {risiko}.'),
            ])],
            ['name' => 'Rancangan Proyek Perubahan (PKN Tingkat II)', 'training_type' => 'pkn2', 'config' => self::wrap([
                self::identity(),
                self::s('proyek', 'Proyek perubahan', [
                    self::f('judul', 'Judul proyek perubahan', 'text'), self::f('diagnosa', 'Diagnosa organisasi dan masalah strategis'),
                    self::f('terobosan', 'Terobosan / inovasi yang diusulkan'), self::f('tujuan', 'Tujuan dan sasaran'),
                    self::f('kinerja', 'Ukuran keberhasilan (indikator kinerja)', 'table', true, ['columns' => ['Indikator', 'Kondisi awal', 'Target']]),
                ]),
                self::s('tata', 'Tata kelola', [
                    self::f('stakeholder', 'Pemangku kepentingan strategis', 'table', true, ['columns' => ['Pemangku kepentingan', 'Peran', 'Strategi']]),
                    self::f('tahapan', 'Tahapan jangka pendek, menengah, panjang', 'table', true, ['columns' => ['Tahap', 'Kegiatan', 'Waktu', 'Output']]),
                    self::f('anggaran', 'Sumber daya dan anggaran', 'textarea', false),
                ]),
            ], [
                self::o('pendahuluan', 'BAB I Pendahuluan', 'Susun latar belakang dari {diagnosa}, judul {judul}, terobosan {terobosan}, dan tujuan {tujuan}.'),
                self::o('kinerja', 'BAB II Ukuran keberhasilan', 'Sajikan tabel indikator {kinerja} dan jelaskan dampaknya.'),
                self::o('tata', 'BAB III Tata kelola dan tahapan', 'Uraikan {stakeholder}, {tahapan}, dan {anggaran}.'),
            ])],
            ['name' => 'Learning journal', 'training_type' => null, 'config' => self::wrap([
                self::s('jurnal', 'Jurnal belajar', [
                    self::f('topik', 'Topik / mata pelatihan', 'text'), self::f('tanggal', 'Tanggal', 'date'),
                    self::f('dipelajari', 'Apa yang saya pelajari'), self::f('perasaan', 'Apa yang saya rasakan'),
                    self::f('penerapan', 'Bagaimana saya akan menerapkannya di tempat kerja'),
                ]),
            ], [self::o('jurnal', 'Learning journal', 'Rangkai {dipelajari}, {perasaan}, dan {penerapan} tentang {topik} menjadi jurnal reflektif yang runtut.')], ['size' => 'A5', 'width_mm' => 148, 'height_mm' => 210, 'margin' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25]])],
            ['name' => 'Lesson learned studi lapangan', 'training_type' => null, 'config' => self::wrap([
                self::s('lapangan', 'Studi lapangan', [
                    self::f('lokus', 'Lokus / instansi yang dikunjungi', 'text'), self::f('tanggal', 'Tanggal kunjungan', 'date'),
                    self::f('praktik', 'Praktik baik yang ditemukan'), self::f('faktor', 'Faktor kunci keberhasilan'),
                    self::f('adopsi', 'Yang bisa diadopsi di instansi saya'), self::f('foto', 'Foto / dokumentasi', 'file', false),
                ]),
            ], [
                self::o('temuan', 'Temuan praktik baik', 'Uraikan praktik baik {praktik} di {lokus} dan faktor kunci {faktor}.'),
                self::o('adopsi', 'Rencana adopsi', 'Susun rencana adopsi dari {adopsi} yang realistis untuk instansi peserta.'),
            ])],
        ];
    }
}
