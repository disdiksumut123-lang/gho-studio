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

## Selesai sesudah v0.1 (14 Nov siang)
- Suara: tombol mikrofon di editor dan kolom jawaban (ElevenLabs Scribe, cadangan Web Speech API browser), tombol "Dengarkan" pada materi dan tanggapan (ElevenLabs TTS, cadangan suara browser). Kunci di Pengaturan › WhatsApp & suara. Kode: `app/Services/Voice.php`, `VoiceController`, bagian akhir `public/js/gho.js`. Format API ElevenLabs belum diuji dengan kunci asli.
- Hemat kuota: batas bulanan dan harian per peserta untuk layanan cerdas dan suara (`app/Services/Quota.php`, dicek di `AiClient::ask`), penghitung di halaman pengaturan. Per aktivitas: boleh/tidak tempel teks, maks. karakter, maks. lama rekaman (0 = mikrofon mati).
- Pembuat isian/output: tabel `form_templates` dan `documents`, jenis aktivitas `formulir`, pembuat di halaman aktivitas (`public/js/gho-form.js`), pengaturan kertas dan huruf (`app/Support/Page.php`). BELUM: halaman peserta untuk mengisi formulir, penyusunan dokumen, tampilan cetak, templat bawaan.

## Selesai: isian dan output dinamis (14 Nov)
- Jenis aktivitas `formulir` ("Formulir dan dokumen"). Skema di `activities.config`: `form.sections[].fields[]` (text, textarea, number, date, select, radio, checkbox, table, file), `output.sections[] {key,title,prompt}` dengan rujukan `{kode_isian}`, `page` (A4, A5, F4, Letter, Legal, custom; orientasi; margin), `fonts` (huruf dan ukuran isian/output, judul, spasi).
- Pembuat: `resources/views/fas/activity-form.blade.php` + `public/js/gho-form.js`. Templat tersimpan di tabel `form_templates`; 5 templat siap pakai dari `app/Support/FormPresets.php` (diisi seeder, aman dijalankan ulang).
- Peserta: `resources/views/pes/partials/formulir.blade.php` + `public/js/gho-pform.js` (simpan otomatis ke `answers` item_key `form`, versi baru tiap perubahan). Kirim memeriksa isian wajib dan menyimpan unggahan.
- Dokumen: `Pes\FormController` (save, submit, document, compose, update), tampilan `pes/document.blade.php` dengan `@page` sesuai kertas. Susun per bagian (satu panggilan layanan cerdas per bagian, `Prompts::composeSection`), bisa disunting langsung, dicetak/PDF. Staf menyetujui atau mengembalikan; daftar dokumen ada di halaman Tinjau (coach/fasilitator).
- Prototipe: menu Coach › Pembuat formulir, dan Peserta › Rancangan memakai isian dinamis + halaman dokumen.
- Belum: cek mutu otomatis per bagian isian (seperti Negasi-Fokus-Lokus) di aplikasi nyata; unduh Word (.docx).

## Selesai: ukuran tampilan video dan eBook (14 Nov malam)
- Permintaan pemilik produk: tautan video/eBook (YouTube, Heyzine) bisa diatur ukuran tampilannya, misalnya 1920x1080, dan bisa ukuran bebas.
- `app/Support/Frame.php`: rasio siap pakai (16:9, 4:3, 1:1, 9:16, 21:9) atau "custom" dengan lebar/tinggi piksel sendiri. Disimpan di `activities.config.frame`.
- Pembuat aktivitas: pilihan "Ukuran tampilan" pada kartu Media untuk jenis video dan eBook (`resources/views/fas/activity-form.blade.php`).
- Peserta: kotak video/eBook memakai CSS `aspect-ratio` sesuai pilihan, lebar tetap menyesuaikan layar (`resources/views/pes/activity.blade.php`).

## Selesai: unggah video/MP3 langsung, dan sampul audio tanpa bilah hitam (14 Nov malam)
- Permintaan pemilik produk: contoh sampul audio (poster BPSDM Sumut, tegak) harus mengisi penuh kotaknya, tanpa bilah hitam di sisi mana pun; mau unggah MP3 sendiri, bukan cuma tautan.
- Fasilitator sekarang bisa, untuk jenis Video dan Audio, memilih tautan **atau** mengunggah berkas langsung (video_file/audio_file, disimpan di disk privat `local` folder `materi/video` dan `materi/audio`, diputar lewat rute baru `pes.media.file`).
- Jenis Audio: tambahan "Gambar sampul" (tautan atau unggah gambar, kolom `cfg.cover`), ditampilkan penuh di atas kotak sesuai rasio "Ukuran tampilan" (sekarang berlaku juga untuk audio) dengan `object-fit:cover` — gambar dipotong mengikuti kotak, tidak pernah ada bilah hitam.
- Kode: `App\Http\Controllers\Fas\ActivityController::payload()` (unggahan dan simpan cfg), `App\Http\Controllers\Pes\LearnController::mediaFile()` + rute `belajar/aktivitas/{activity}/berkas/{field}` (penyaji berkas privat), `resources/views/fas/activity-form.blade.php`, `resources/views/pes/activity.blade.php`.
- Belum diuji dengan unggahan sungguhan (tanpa composer/database di sandbox ini) — coba unggah satu video dan satu MP3 + sampul begitu terpasang di Hostinger.

## Selesai: jenis isian formulir dinamis diperlengkap, dan jenis/ukuran huruf di editor (14 Nov malam)
- Permintaan pemilik produk: lengkapi jenis isian formulir seperti contoh tangkapan layar (ada ~17 jenis di aplikasi pembanding), dan lengkapi editor teks dengan pilihan jenis huruf selain ukuran huruf.
- Jenis isian formulir dinamis bertambah dari 9 jadi 16: `time` (Waktu), `url` (Tautan/URL), `whatsapp` (Nomor WhatsApp), `photo` (Unggah foto — seperti berkas tapi khusus gambar, bisa dari kamera HP), `linear_scale` (Skala linear, mis. 1–5 dengan label kiri/kanan disimpan di `options`), `signature` (Tanda tangan — digambar di kanvas, disimpan sebagai gambar PNG base64 di jawaban), `narasi` (Teks tampilan — bukan isian, hanya menampilkan teks petunjuk/narasi di antara isian lain, memakai kolom "Petunjuk" yang sudah ada).
- **Belum dikerjakan** (butuh desain lebih lanjut, sengaja dilewati dulu): "Multi Essai" dan "Multi Gambar Deskriptif" (isian berulang/majemuk — perlu struktur data baru), "Dropdown Section" (logika percabangan form — fitur besar tersendiri).
- Kode: `App\Support\Page::FIELD_TYPES` (daftar jenis), `App\Http\Controllers\Pes\FormController` (bersihkan nilai, render teks utk AI, validasi wajib, unggahan foto), `public/js/gho-form.js` (opsi skala linear, sembunyikan "Wajib" utk narasi), `resources/views/pes/partials/formulir.blade.php` + `public/js/gho-pform.js` (tampilan dan kanvas tanda tangan di sisi peserta).
- Editor teks (`resources/views/partials/editor.blade.php`, dipakai di semua tempat yang punya editor lengkap) sekarang punya pilihan **jenis huruf** (daftar sama dengan huruf dokumen output) selain ukuran huruf (sekarang 6 tingkat, tadinya 4).
- Belum diuji dengan database sungguhan — terutama kanvas tanda tangan dan unggahan foto, coba langsung begitu terpasang di Hostinger.

## Peta jalan
Daftar lengkap inovasi berprioritas ada di `ROADMAP-INOVASI.md` (permintaan pemilik produk: selengkap-lengkapnya, untuk masa depan).

## Langkah berikutnya
- Programmer memasang di Hostinger sesuai panduan, pemilik produk mencoba.
- Cek mutu per bagian isian formulir dan unduh Word. Lalu ikuti urutan di ROADMAP-INOVASI.md (A1 UU PDP, A2 log audit, A3 cadangan, A4 keamanan akun).
- Fitur inovasi coach: sesi GROW, catatan tiga pihak, papan milestone + peringatan dini, kartu mentor, gladi seminar, peta pemangku kepentingan.
- Kuis bentuk tambahan: berbasis gambar, PG beralasan, skala sikap, kuis langsung berwaktu.
- Integrasi API resmi YouTube/Meta/TikTok untuk CMS.
- Adopsi 20 aktivitas Coach Lab.
