# Catatan serah terima (untuk melanjutkan dari akun Claude lain)

Gunakan file ini bila kuota satu langganan Claude habis dan pekerjaan dilanjutkan dari langganan lain.

## Cara melanjutkan
1. Buka Claude di akun lain, lalu hubungkan GitHub ke repo `disdiksumut123-lang/gho-studio` (atau unduh repo ini).
2. Berikan pesan pembuka: "Baca README.md, HANDOFF.md, dan SPESIFIKASI-GHO-STUDIO.md di repo gho-studio, lalu lanjutkan pekerjaan dari bagian 'Langkah berikutnya'."
3. Untuk mulai membangun aplikasi: unggah `SPESIFIKASI-GHO-STUDIO.md` dan minta Claude membangun Tahap 1 sesuai bab 21.

## Status per 14 November 2026 (sore)
- Spesifikasi v2, prototipe klik v0.2, dan **aplikasi Laravel v0.1** di folder `aplikasi/` sudah ada.
- Aplikasi diuji lokal (sqlite + penyedia tiruan): 27 halaman tanpa galat, alur peserta dari undangan WhatsApp → daftar → materi → video → tugas → studi kasus → kuis → laporan lulus, tampilan HP 375 px tanpa geser samping.
- Cara pasang: `aplikasi/PANDUAN-PASANG-HOSTINGER.md`.

## Peta kode aplikasi
- Rute: `aplikasi/routes/web.php` (prefix `fasilitator`, `coach`, `cms`, `belajar`, `pengaturan`, plus `/l/{token}` tautan bagikan dan `/undangan/{token}`).
- Aturan kunci: `app/Services/Progress.php`. Laporan: `app/Services/Report.php`. Pendaftaran/undangan: `app/Services/Enroll.php`.
- Penyedia layanan cerdas: `app/Services/AiClient.php` (openai_compatible, gemini, anthropic) dan instruksi di `app/Services/Prompts.php`.
- CMS: `app/Http/Controllers/Cms/CmsController.php`, pengirim `app/Services/Publisher.php` (fungsi `send` masih menunggu aplikasi resmi platform).
- WhatsApp: `app/Services/WhatsApp.php` (Fonnte/Wablas) dan tautan wa.me.
- Tampilan: `resources/views`, gaya `public/css/gho.css`, skrip `public/js/gho.js` (editor, persamaan MathLive, pelacak video/eBook, studi kasus, penyunting soal).
- Terjemahan Inggris: `lang/en.json` (kunci = teks Indonesia).

## Keputusan tambahan dari pemilik produk (14 Nov)
- Login: akun pemilik memegang Super Admin, Coach, Fasilitator, CMS; peserta login sendiri.
- CMS hanya untuk akun pemilik (peran admin/cms), tidak terlihat peserta.
- Tautan bisa dibagikan per Pelatihan/Agenda/Mata Pelatihan/Aktivitas dengan waktu mulai dan akhir.
- Tautan Pelatihan membuka seluruh pohon; peserta mengerjakan berurutan (aktivitas 4 butuh 1–3 selesai, mata pelatihan berikutnya butuh sebelumnya selesai).
- Laporan per Aktivitas, Mata Pelatihan, Agenda sesuai posisi peserta.
- Peserta masuk dengan persetujuan, undangan (nama, email, atau nomor WhatsApp), atau unggah Excel.
- Hosting Hostinger (paket belum diketahui), API utama DeepSeek, ingin banyak pilihan penyedia. Bahasa Indonesia dan Inggris.

## Keputusan pemilik produk yang wajib dijaga
- Coach hanya: Rancangan Aktualisasi (Latsar), Aksi Perubahan (PKP, PKA), Proyek Perubahan (PKN Tk II), Learning journal (PKP, PKA, PKN), Lesson learned studi lapangan (PKP, PKA), Lesson learned (PKN), plus fitur khas coach (sesi GROW, catatan tiga arah, milestone dan peringatan dini, kartu progres mentor, latihan seminar). Semua aktivitas lain milik Fasilitator.
- Tidak boleh ada kata "AI" di nama menu atau aktivitas, walaupun AI bekerja di balik layar.
- Struktur: Pelatihan › Agenda › Mata Pelatihan › Aktivitas. Tiap tingkat punya jadwal; bawaannya berurutan; keterkaitan bisa dimatikan per item.
- Video, audio, eBook (Heyzine) wajib selesai sebelum aktivitas berikutnya terbuka.
- Kuis dalam 10 bentuk (pilihan ganda biasa, kompleks, benar/salah per pernyataan, menjodohkan, mengurutkan, isian, berbasis gambar, beralasan, skala sikap, kuis langsung berwaktu).
- Unggah dokumen opsional per aktivitas; bisa dimatikan sehingga peserta wajib mengetik. Editor seperti Word dengan persamaan.
- Studi kasus interaktif satu alur; setiap jawaban disimpan ke database sebelum tanggapan dibuat.
- CMS: unggah sekali dari Gho Studio, terbit di YouTube, Facebook, Instagram, TikTok, dan lainnya. Posting langsung di Facebook tidak bisa diteruskan otomatis ke TikTok.
- Tampilan mudah, kekinian, responsif; navigasi HP maksimal 4 menu + Lainnya.
- Aplikasi lama yang diadopsi: coach.gho-elearning.com dan Coach Labs (coach-labs.gho-elearning.com). Kredensial tidak disimpan di repo; minta langsung ke pemilik produk bila perlu.

## SEDANG DIKERJAKAN: isian dan output dinamis (permintaan 14 Nov, 11.15)
Pemilik produk ingin isian rancangan TIDAK statis (contoh salah: "Identitas umum" dengan kolom tetap di prototipe). Belum ada kode untuk ini; mulai dari rancangan berikut.

Permintaan:
1. **Pembuat isian**: fasilitator/coach menambah, menghapus, mengurutkan bagian dan isian dengan jenis apa pun: teks pendek, paragraf (editor lengkap + persamaan), angka, tanggal, pilihan (dropdown/radio), centang banyak, tabel (kolom ditentukan, baris bisa ditambah peserta), unggah berkas.
2. **Pembuat output**: bagian output bisa ditambah dan dihapus; tiap bagian punya perintah penyusunan yang merujuk isian, misalnya `{latar_belakang}`.
3. **Templat output**: ukuran kertas A4, A5, F4/Folio, Letter, Legal, ukuran bebas (mm), orientasi, margin.
4. **Huruf**: jenis dan ukuran huruf bisa diatur untuk isian dan untuk output.
5. Berlaku untuk rancangan coach dan tugas lain yang cocok. Prototipe juga perlu diperbarui.

Rancangan teknis yang disarankan:
- Jenis aktivitas baru `formulir` di `Activity::TYPES` (nama tampil: "Formulir dan dokumen"). Skema disimpan di `activities.config`:
  `form.sections[] = {key, title, help, fields[] = {key, label, type: text|textarea|number|date|select|radio|checkbox|table|file, required, options[], columns[], help}}`,
  `output.sections[] = {key, title, prompt}`, `page = {size: A4|A5|F4|Letter|Legal|custom, width_mm, height_mm, orientation, margin_mm{top,right,bottom,left}}`,
  `fonts = {input_family, input_size, output_family, output_size, heading_size}`.
- Tabel baru `form_templates` (name, training_type, config json, created_by) agar skema bisa disimpan dan dipakai ulang; seeder templat: Rancangan Aktualisasi (Latsar), Aksi Perubahan (PKP/PKA), Proyek Perubahan (PKN II), Learning journal, Lesson learned (lihat bab 7 dan Lampiran B spesifikasi untuk bagian-bagiannya).
- Tabel baru `documents` (activity_id, user_id, answer_id, sections json {key: html}, status draf/ditinjau/disetujui, updated_by). Jawaban isian disimpan di `answers` sebagai JSON (`item_key='form'`, simpan otomatis per versi seperti tugas).
- Penyusunan output: satu panggilan `AiClient` per bagian output (agar tidak terpotong dan bisa diulang per bagian), prompt bagian diisi nilai isian. Simpan dulu jawaban, baru susun (pola yang sama dengan studi kasus).
- Tampilan dokumen: halaman HTML dengan CSS `@page { size: <w>mm <h>mm; margin: ... }` dan huruf sesuai pengaturan, tombol Cetak/PDF lewat browser; coach bisa mengedit dan menyetujui per bagian.
- Pembuat isian/output di `resources/views/fas/activity-form.blade.php` (kartu `.cfg[data-for=formulir]`) dengan JS mirip `ghoQuiz` di `public/js/gho.js`, hasilnya JSON di input tersembunyi `cfg[form]`, `cfg[output]` (ubah `ActivityController::payload` agar men-decode JSON ini).

## Peta jalan
Daftar lengkap inovasi berprioritas ada di `ROADMAP-INOVASI.md` (permintaan pemilik produk: selengkap-lengkapnya, untuk masa depan).

## Langkah berikutnya
- Programmer memasang di Hostinger sesuai panduan, pemilik produk mencoba.
- Selesaikan isian dan output dinamis di atas (termasuk templat rancangan coach dan cek mutu per bagian).
- Fitur inovasi coach: sesi GROW, catatan tiga pihak, papan milestone + peringatan dini, kartu mentor, gladi seminar, peta pemangku kepentingan.
- Kuis bentuk tambahan: berbasis gambar, PG beralasan, skala sikap, kuis langsung berwaktu.
- Integrasi API resmi YouTube/Meta/TikTok untuk CMS.
- Adopsi 20 aktivitas Coach Lab.
