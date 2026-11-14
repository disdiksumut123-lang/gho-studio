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

## Langkah berikutnya
- Programmer memasang di Hostinger sesuai panduan, pemilik produk mencoba.
- Tahap 5: rancangan coach (Rancangan Aktualisasi, Aksi Perubahan, Proyek Perubahan) dengan cek mutu per bagian.
- Fitur inovasi coach: sesi GROW, catatan tiga pihak, papan milestone + peringatan dini, kartu mentor, gladi seminar, peta pemangku kepentingan.
- Kuis bentuk tambahan: berbasis gambar, PG beralasan, skala sikap, kuis langsung berwaktu.
- Integrasi API resmi YouTube/Meta/TikTok untuk CMS.
- Adopsi 20 aktivitas Coach Lab.
